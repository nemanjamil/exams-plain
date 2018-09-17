<?php
///namespace exe;

class exams
{
    protected $db;

    public function __construct($host = "localhost", $username = "root", $password = null, $db = "bpmspace_sqms_v6_a")
    {
       $this->dbConn = new mysqli('localhost','root','','bpmspace_sqms_v6_a');
    }

    public function index()
    {
        $sql = "SELECT * FROM sqms_exam_version";
        $result = $this->dbConn->query($sql);
        if($result){
            while ($row = $result->fetch_object()){
                $user_arr[] = $row;
            }
        }
        return $this->jsonenc($user_arr);

    }

    public function hashsalt()
    {
        return response()->json(config('constants.hash_salt'));
    }

    protected function  showMore($data, $hash_salt)
    {
        $idvcsv = '';
        foreach ($data as $k => $v) {
            $onev = explode("|", $v);
            $idv[] = (int) $onev[0];
            $idvcsv .= $onev[0] . ',';
        }
        asort($idv); // just in case
        
        $query_trim = rtrim($idvcsv,",");
        $sql = "SELECT * FROM sqms_exam_version WHERE sqms_exam_version_id IN ($query_trim)";
        $result = $this->dbConn->query($sql);
        while ($row = $result->fetch_object()){
            $queryExams[] = $row;
        }

        $examNamefull = "COMBI ";
        $idset = '';
        $i = 0;
       

        foreach ($queryExams as $k => $v) {
            $examNamefull .= $this->striphtml($v->sqms_exam_version_name) . "($i), ";

            $sqms_exam_version_id = sprintf("%010d", $v->sqms_exam_version_id);
            $sqms_exam_set = sprintf("%05d", $v->sqms_exam_set);
            $sqms_exam_version = sprintf("%05d", $v->sqms_exam_version);
            $sqms_exam_version_sample_set = $v->sqms_exam_version_sample_set;
            $idset .= $sqms_exam_version_id . '-' . $sqms_exam_set . '-' . $sqms_exam_version . '-' . $sqms_exam_version_sample_set . '-';
            $i++;
        }
      
        return $this->showAdv($idvcsv, $examNamefull, $idset, $hash_salt, $v->sqms_exam_set, $v->sqms_exam_version, $sqms_exam_version_sample_set);

    }

    protected function striphtml($value){

        $allowed = "<div><span><pre><p><br><hr><hgroup><h1><h2><h3><h4><h5><h6>";
        $allowed .= "<ul><ol><li><dl><dt><dd><strong><em><b><i>";
        $allowed .= "<img><a><abbr><address><blockquote><area><audio><video>";
        $allowed .= "<caption><table><tbody><td><tfoot><th><thead><tr><sup><sub>";

        return  htmlspecialchars(strip_tags($value,$allowed));
    }

    protected function generateXMLMore($data, $hash_salt)
    {
        $idvcsv = '';
        foreach ($data as $k => $v) {
            $onev = explode("|", $v);
            $idv[] = $onev[0];
            $idvcsv .= $onev[0] . ',';
        }

        
        $query_trim = rtrim($idvcsv,",");
        $sql = "SELECT * FROM sqms_exam_version WHERE sqms_exam_version_id IN ($query_trim)";
        $result = $this->dbConn->query($sql);
        while ($row = $result->fetch_object()){
            $queryExams[] = $row;
        }


        $domtree = new \DOMDocument('1.0', 'UTF-8');
        $xmlRoot = $domtree->createElement("quiz");
        $xmlRoot = $domtree->appendChild($xmlRoot);

        $examNamefull = "COMBI ";

        $idset = '';
        $i = 0;
        foreach ($queryExams as $k => $v) {
            $examNamefull .= $this->striphtml($v->sqms_exam_version_name) . "($i), ";

            $sqms_exam_version_id = sprintf("%010d", $v->sqms_exam_version_id);
            $sqms_exam_set = sprintf("%05d", $v->sqms_exam_set);
            $sqms_exam_version = sprintf("%05d", $v->sqms_exam_version);
            $sqms_exam_version_sample_set = $v->sqms_exam_version_sample_set;
            $idset .= $sqms_exam_version_id . '-' . $sqms_exam_set . '-' . $sqms_exam_version . '-' . $sqms_exam_version_sample_set . '-';

            $i++;
        }

        $xmlRoot->appendChild($domtree->createElement('examName', $examNamefull));
        $xmlRoot->appendChild($domtree->createElement('set', $v->sqms_exam_set));
        $xmlRoot->appendChild($domtree->createElement('version', $v->sqms_exam_version));
        $xmlRoot->appendChild($domtree->createElement('SampleSet', $v->sqms_exam_version_sample_set));
        $xmlRoot->appendChild($domtree->createElement('id', rtrim($idset, "-")));

        $numberOfQuestionTotal = $this->numberOfQuestionTotal($idvcsv);

        $xmlRoot->appendChild($domtree->createElement('questionTotal', $numberOfQuestionTotal[0]->questionTotal));

        // ADD COMMENT IN QUIZ
        $commentlink = "\n \"examName \" : " . "\"" . $v->sqms_exam_version_name . "\" \n";
        $commentlink .= "\"set \" : " . $v->sqms_exam_set . " \n";
        $commentlink .= "\"version \" : " . $v->sqms_exam_version . " \n";
        $commentlink .= "\"SampleSet \" : " . $v->sqms_exam_version_sample_set . " \n";
        $commentlink .= "\"id \" : " . "\"" . rtrim($idset, "-") . "\" \n";
        $commentlink .= "\"questionTotal \" : " . $numberOfQuestionTotal[0]->questionTotal . " \n";

        $comment = $domtree->createComment($commentlink);
        $xmlRoot->appendChild($comment);


        $sql = "CALL examquestions('" . rtrim($idvcsv, ", ") . "')";
        $result = $this->dbConn->query($sql);
        $listanswers = [];
        while ($row = $result->fetch_object()){
        $numberOfQuestionTotalExam[] = $row;
        }
        $result->close();
        $this->dbConn->next_result();


          foreach ($numberOfQuestionTotalExam as $k => $v) {

            $sprint_sqms_question_id = sprintf("%010d", $v->sqms_question_id);
            $qarr['question_id'] = $sprint_sqms_question_id;
            $qarr['question_text'] = $v->question;
            $qarr['answers'] = [];
            $qarr['answersSelected'] = [];

            $question_question = $v->question;

            $answer_is_sprint = '';
            
            $sql = "CALL listanswers($v->sqms_exam_version_id,$v->sqms_question_id)";
            $result = $this->dbConn->query($sql);
            $listanswers = []; // reset array
            while ($row = $result->fetch_object()){
             $listanswers[] = $row;
            }
            $result->close();
            $this->dbConn->next_result();



            if (count($listanswers) > 0) {
                foreach ($listanswers as $k => $v) {
                    $answer_is_sprint .= '-' . sprintf("%010d", $v->sqms_answer_id);
                }
            }

            // QUESTION
            $currentTrack = $domtree->createElement("question");
            $currentTrack = $xmlRoot->appendChild($currentTrack);
            $type = $domtree->createAttribute("type");
            $currentTrack->appendChild($type);
            $multichoiseset = $domtree->createTextNode("multichoiceset");
            $type->appendChild($multichoiseset);

            // NAME
            $name = $currentTrack->appendChild($domtree->createElement('name'));
            $text = $domtree->createElement("text");
            $name->appendChild($text);
            $text->appendChild($domtree->createCDATASection($sprint_sqms_question_id . $answer_is_sprint));

            // QUESTIONTEXT
            $questiontext = $currentTrack->appendChild($domtree->createElement('questiontext'));
            $format = $domtree->createAttribute("format");
            $questiontext->appendChild($format);
            $html = $domtree->createTextNode("html");
            $format->appendChild($html);

            $text = $questiontext->appendChild($domtree->createElement("text"));
            $text->appendChild($domtree->createCDATASection($question_question));

            // ANSWER
            if (count($listanswers) > 0) {
                foreach ($listanswers as $k => $v) {

                    $answer = $currentTrack->appendChild($domtree->createElement('answer'));
                    $fraction = $domtree->createAttribute("fraction");
                    $answer->appendChild($fraction);
                    $numberfraction = $domtree->createTextNode("100.00000");
                    $fraction->appendChild($numberfraction);

                    $text = $answer->appendChild($domtree->createElement("text"));
                    $text->appendChild($domtree->createCDATASection($v->answer));

                }
            }
        }

        $currentTrack->appendChild($domtree->createElement('shuffleanswers', 1));
        $currentTrack->appendChild($domtree->createElement('single', false));
        $currentTrack->appendChild($domtree->createElement('answernumbering', 'abc'));

        return $domtree->saveXML();
    }

    protected function jsonenc($rt){
        return json_encode($rt,JSON_UNESCAPED_UNICODE);
    }

    public function show($request)
    {

        $data = $request["data"];
        $hash_salt = $request["hash_salt"];
        $savedata = $request["savedata"];

        if (!$hash_salt) {
            $rt = [
                'message' => 'No hash_salt, please add one',
                'status' => false
            ];
            return $this->jsonenc($rt);
            die;
        }

        if (count($data) > 1) {
            $json = $this->showMore($data, $hash_salt);
            $xml = $this->generateXMLMore($data, $hash_salt);

            var_dump($xml);
            die;
            $linkdofile = $this->saveToStorage($savedata, $json, $xml, $hash_salt);

            $rer = [
                'json' => $json,
                'xml' => $xml,
                'message' => 'Ok',
                'status' => true,
                'savedata' => $linkdofile
            ];
            return $this->jsonenc($rer);


        } else {
            $json = $this->showOne($data, $hash_salt);
            $xml = $this->generateXML($data, $hash_salt);
            $linkdofile = $this->saveToStorage($savedata, $json, $xml, $hash_salt);

            $rer = [
                'json' => $json,
                'xml' => $xml,
                'message' => 'Ok',
                'status' => true,
                'savedata' => $linkdofile
            ];
            return $this->jsonenc($rer);
        }
    }

    protected function saveToStorage($savedata, $json, $xml, $hash_salt)
    {
        if ($savedata == 'download') {
            $namefile = str_replace("-", "", $json["id"]);
            $publiclink = 'storage/' . $namefile;
            // Storage::makeDirectory($publiclink);
            // Storage::put($publiclink . '/' . $namefile . '.json', json_encode($json));
            // Storage::put($publiclink . '/' . $namefile . '.xml', $xml);
            // Storage::put($publiclink . '/' . $namefile . '.SALT', $hash_salt);
        }  else {
            $namefile = false;
        }

        return $namefile;
    }

    protected function numberOfQuestionTotal($idvcsv)
    {
        //return DB::select("CALL countexams('" . rtrim($idvcsv, ", ") . "')");
        //return  $this->dbConn->rawQuery("CALL countexams('" . rtrim($idvcsv, ", ") . "')");
        
        $sql = "CALL countexams('" . rtrim($idvcsv, ", ") . "')";
        $result = $this->dbConn->query($sql);
        while ($row = $result->fetch_object()){
            $q[] = $row;
        }
        $result->close();
        $this->dbConn->next_result();

        return $q;
        
    }

    protected function showAdv($idvcsv, $examNamefull, $idset, $hash_salt, $sqms_exam_set, $sqms_exam_version, $sqms_exam_version_sample_set)
    {

        $numberOfQuestionTotal = $this->numberOfQuestionTotal($idvcsv);
        
        $response["examName"] = rtrim($examNamefull, ", ");
        $response["set"] = $sqms_exam_set;
        $response["version"] = $sqms_exam_version;
        $response["SampleSet"] = ($sqms_exam_version_sample_set) ? true : false;
        $response["id"] = rtrim($idset, "-");
        $response["uid"] = 0;
        $response["time"] = 0;
        $response["starttime"] = 0;
        $response["duration"] = 0;
        $response["questionIndex"] = 0;
        $response["questionTotal"] = $numberOfQuestionTotal[0]->questionTotal;
        $response["firstname"] = "";
        $response["lastname"] = "";
        $response["answerOptions"] = [];


        //$numberOfQuestionTotalExam = DB::select("CALL examquestions('" . rtrim($idvcsv, ", ") . "')");
        
        $sql = "CALL examquestions('" . rtrim($idvcsv, ", ") . "')";
        $result = $this->dbConn->query($sql);
        while ($row = $result->fetch_object()){
            $numberOfQuestionTotalExam[] = $row;
        }
        $result->close();
        $this->dbConn->next_result();

        $tren = [];
       
       

        foreach ($numberOfQuestionTotalExam as $k => $v) {
            $sprint_sqms_question_id = sprintf("%010d", $v->sqms_question_id);
            $qarr['question_id'] = $sprint_sqms_question_id;
            $qarr['question_text'] = $v->question;
            //$qarr['sqms_exam_version_id'] = $v->sqms_exam_version_id;
            $qarr['answers'] = [];
            $qarr['answersSelected'] = [];


            //$numberOfQuestionTotal = DB::select("CALL examquestions('" . rtrim($idvcsv, ", ") . "')");

            $ls = [];
            
            $sql = "CALL listanswers($v->sqms_exam_version_id,$v->sqms_question_id)";
            $result = $this->dbConn->query($sql);
            $listanswers = [];
            while ($row = $result->fetch_object()){
                $listanswers[] = $row;
            }
            $result->close();
            $this->dbConn->next_result();

            if (count($listanswers) > 0) {
                $varHashFirstNumber = true;  // remove line in prod
                $firstNumberforHash = '';
                foreach ($listanswers as $k => $v) {

                    $answer_is_sprint_int = (int) $v->sqms_answer_id;
                    $correct_answ = $v->correct;
                    $answer_is_sprint = sprintf("%010d", $v->sqms_answer_id);
                    $forls['answer_id'] = $answer_is_sprint;
                    $forls['answer_text'] = $v->answer;
                    $forls['correct'] = $correct_answ;

                    if ($correct_answ == 1) {
                            $firstNumberforHash .= $answer_is_sprint;
                    }

                    $answerHash = $firstNumberforHash . $hash_salt; //config('constants.hash_salt');


                    array_push($ls, $forls);
                }

                if (!$firstNumberforHash) {
                    return response()->json([
                        'message' => 'No firstNumberforHash fix the dataBase -> must have one ore more correct answer : '.$answer_is_sprint_int,
                        'status' => false
                    ]);
                    die;
                }

                $qarr['answers'] = $ls;
            }
            //$qarr['answersHashORG'] = "hash('sha512', $answerHash)"; // https://www.tools4noobs.com/online_php_functions/sha512/
            $qarr['answersHash'] = hash('sha512', $answerHash);
            //$qarr['answersHashBase64encode'] = base64_encode(hash('sha512', $answerHash));
            array_push($tren, $qarr);
        }

        $response["examQuestions"] = $tren;

        return $response;
    

    }

    protected function showOne($data, $hash_salt)
    {

        $onev = explode("|", $data[0]);
        $idv = $onev[0];
        $idvcsv = $onev[0];



        $queryExams = DB::table('sqms_exam_version')->where('sqms_exam_version_id', $idv)->get();

        $idset = '';
        $i = 0;

        foreach ($queryExams as $k => $v) {
            $examNamefull = $v->sqms_exam_version_name;
            $sqms_exam_version_id = sprintf("%010d", $v->sqms_exam_version_id);
            $sqms_exam_set = sprintf("%05d", $v->sqms_exam_set);
            $sqms_exam_version = sprintf("%05d", $v->sqms_exam_version);
            $sqms_exam_version_sample_set = $v->sqms_exam_version_sample_set;
            $idset = $sqms_exam_version_id . '-' . $sqms_exam_set . '-' . $sqms_exam_version . '-' . $sqms_exam_version_sample_set . '-';
            $i++;
        }

        return $this->showAdv($idvcsv, $examNamefull, $idset, $hash_salt, $v->sqms_exam_set, $v->sqms_exam_version, $sqms_exam_version_sample_set);

    }

    protected function generateXML($data, $hash_salt)
    {

        $idvcsv = '';
        foreach ($data as $k => $v) {
            $onev = explode("|", $v);
            $idv[] = $onev[0];
            $idvcsv .= $onev[0] . ',';
        }

        $queryExams = DB::table('sqms_exam_version')->where('sqms_exam_version_id', $idv)->get();


        $domtree = new \DOMDocument('1.0', 'UTF-8');
        $xmlRoot = $domtree->createElement("quiz");
        $xmlRoot = $domtree->appendChild($xmlRoot);

        $idset = '';
        $i = 0;
        foreach ($queryExams as $k => $v) {
            $sqms_exam_version_id = sprintf("%010d", $v->sqms_exam_version_id);
            $sqms_exam_set = sprintf("%05d", $v->sqms_exam_set);
            $sqms_exam_version = sprintf("%05d", $v->sqms_exam_version);
            $sqms_exam_version_sample_set = $v->sqms_exam_version_sample_set;
            $idset = $sqms_exam_version_id . '-' . $sqms_exam_set . '-' . $sqms_exam_version . '-' . $sqms_exam_version_sample_set . '-';

            $xmlRoot->appendChild($domtree->createElement('examName', $v->sqms_exam_version_name));
            $xmlRoot->appendChild($domtree->createElement('set', $v->sqms_exam_set));
            $xmlRoot->appendChild($domtree->createElement('version', $v->sqms_exam_version));
            $xmlRoot->appendChild($domtree->createElement('SampleSet', $v->sqms_exam_version_sample_set));
            $xmlRoot->appendChild($domtree->createElement('id', rtrim($idset, "-")));

            $numberOfQuestionTotal = $this->numberOfQuestionTotal($idvcsv);

            $xmlRoot->appendChild($domtree->createElement('questionTotal', $numberOfQuestionTotal[0]->questionTotal));

            $i++;
        }

        // ADD COMMENT IN QUIZ
        $commentlink = "\n \"examName \" : " . "\"" . $v->sqms_exam_version_name . "\" \n";
        $commentlink .= "\"set \" : " . $v->sqms_exam_set . " \n";
        $commentlink .= "\"version \" : " . $v->sqms_exam_version . " \n";
        $commentlink .= "\"SampleSet \" : " . $v->sqms_exam_version_sample_set . " \n";
        $commentlink .= "\"id \" : " . "\"" . rtrim($idset, "-") . "\" \n";
        $commentlink .= "\"questionTotal \" : " . $numberOfQuestionTotal[0]->questionTotal . " \n";

        $comment = $domtree->createComment($commentlink);
        $xmlRoot->appendChild($comment);


        $numberOfQuestionTotalExam = DB::select("CALL examquestions('" . rtrim($idvcsv, ", ") . "')");
        foreach ($numberOfQuestionTotalExam as $k => $v) {


            $sprint_sqms_question_id = sprintf("%010d", $v->sqms_question_id);
            $qarr['question_id'] = $sprint_sqms_question_id;
            $qarr['question_text'] = $v->question;
            $qarr['answers'] = [];
            $qarr['answersSelected'] = [];

            $question_question = $v->question;

            $answer_is_sprint = '';
            $listanswers = DB::select("CALL listanswers($v->sqms_exam_version_id,$v->sqms_question_id)");
            if (count($listanswers) > 0) {
                foreach ($listanswers as $k => $v) {
                    $answer_is_sprint .= '-' . sprintf("%010d", $v->sqms_answer_id);
                }
            }

            // QUESTION
            $currentTrack = $domtree->createElement("question");
            $currentTrack = $xmlRoot->appendChild($currentTrack);
            $type = $domtree->createAttribute("type");
            $currentTrack->appendChild($type);
            $multichoiseset = $domtree->createTextNode("multichoiceset");
            $type->appendChild($multichoiseset);

            // NAME
            $name = $currentTrack->appendChild($domtree->createElement('name'));
            $text = $domtree->createElement("text");
            $name->appendChild($text);
            $text->appendChild($domtree->createCDATASection($sprint_sqms_question_id . $answer_is_sprint));

            // QUESTIONTEXT
            $questiontext = $currentTrack->appendChild($domtree->createElement('questiontext'));
            $format = $domtree->createAttribute("format");
            $questiontext->appendChild($format);
            $html = $domtree->createTextNode("html");
            $format->appendChild($html);

            $text = $questiontext->appendChild($domtree->createElement("text"));
            $text->appendChild($domtree->createCDATASection($question_question));

            // ANSWER
            if (count($listanswers) > 0) {
                foreach ($listanswers as $k => $v) {

                    $answer = $currentTrack->appendChild($domtree->createElement('answer'));
                    $fraction = $domtree->createAttribute("fraction");
                    $answer->appendChild($fraction);
                    $numberfraction = $domtree->createTextNode("100.00000");
                    $fraction->appendChild($numberfraction);

                    $text = $answer->appendChild($domtree->createElement("text"));
                    $text->appendChild($domtree->createCDATASection($v->answer));

                }
            }
        }

        $currentTrack->appendChild($domtree->createElement('shuffleanswers', 1));
        $currentTrack->appendChild($domtree->createElement('single', false));
        $currentTrack->appendChild($domtree->createElement('answernumbering', 'abc'));

        return $domtree->saveXML();
    }


}