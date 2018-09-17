<?php
$conn = new mysqli('localhost','root','','bpmspace_sqms_v6_a');
$sql = "CALL listanswers(54681,183)";
$result = $conn->query($sql);
if($result){
    while ($row = $result->fetch_object()){
        $user_arr[] = $row;
    }
    $result->close();
    $conn->next_result();
}
var_dump($user_arr);
echo "<br><br>";

$sql = "CALL listanswers(54681,183)";
$result = $conn->query($sql);
if($result){
    while ($row = $result->fetch_object()){
        $user_arre[] = $row;
    }
    $result->close();
    $conn->next_result();
}
var_dump($user_arre);


die;
?>