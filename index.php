<?php

// konekcija ka bazi


// RUTE
// imeservera/api/all
// imeservera/api/hashsalt
// imeservera/api/sentdata

//Route::group(['prefix' => '1.0'], function () {
//    Route::get('all', "SqmsExamVersionController@index");
//    Route::get('hashsalt', "SqmsExamVersionController@hashsalt");
//    Route::post('sentdata',"SqmsExamVersionController@show");
//});

//$_GET

// switch

//all // require_once / stranice/all/php

function my_autoloader($class) {
    include 'class/' . $class . '.php';
}
spl_autoload_register('my_autoloader');


$exams = new exams();
echo $exams->testmiki();

?>