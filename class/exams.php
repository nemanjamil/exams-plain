<?php
///namespace exe;

class exams
{
    public function testmiki() {
        echo "aa";
    }


    public function show(Request $request)
    {
        $datafull = $request->all();
        $data = $datafull["data"];
        $hash_salt = $datafull["hash_salt"];
        $savedata = $datafull["savedata"];
        if (!$hash_salt) {
            return response()->json([
                'message' => 'No hash_salt, please add one',
                'status' => false
            ]);
            die;
        }
        if (count($data) > 1) {
            $json = $this->showMore($data, $hash_salt);
            $xml = $this->generateXMLMore($data, $hash_salt);
            $linkdofile = $this->saveToStorage($savedata, $json, $xml, $hash_salt);
            return response()->json([
                'json' => $json,
                'xml' => $xml,
                'message' => 'Ok',
                'status' => true,
                'savedata' => $linkdofile
            ]);
        } else {
            $json = $this->showOne($data, $hash_salt);
            $xml = $this->generateXML($data, $hash_salt);
            $linkdofile = $this->saveToStorage($savedata, $json, $xml, $hash_salt);
            return response()->json([
                'json' => $json,
                'xml' => $xml,
                'message' => 'Ok',
                'status' => true,
                'savedata' => $linkdofile
            ]);
        }
    }



}