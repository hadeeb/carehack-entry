<?php
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Created by PhpStorm.
 * User: farhan
 * Date: 17/11/17
 * Time: 4:00 PM
 */

// API generators

/**
 * @param Request $request
 * @param Response $response
 * @return int
 */
function getAbout(Request $request,Response $response) {
    $team[] = array("alias"=>"GobL1n","name"=>"Hadeeb Farhan","github"=>"hadeeb","email"=>"hadeebfarhan1@gmail.com");
    $team[] = array("alias"=>"kodiyeri","name"=>"Ankith TV","github"=>"tvankith","email"=>"tvankith@gmail.com");
    $team[] = array("alias"=>"white","name"=>"Anandu S","github"=>"","email"=>"anandu97@gmail.com");
    $team[] = array("alias"=>"Ghost","name"=>"Rahul R","github"=>"","email"=>"rahul.fcb11@gmail.com");
    return $response->getBody()->write(json_encode($team));
}

function getInfo(Request $request,Response $response) {
    $depts = Dept::all();
    foreach ($depts as $key=>$dept)
    {
        $doctors = Doctor::where('dept',$dept->id)->get();

        foreach ($doctors as $key1=>$doctor)
        {
            $avail = Availability::where('did',$doctor->id)->get();
            $doctors[$key1]['availability'] = $avail;
        }

        $depts[$key]['doctors'] = $doctors;

    }
    return $response->getBody()->write(json_encode($depts));
}

function getAppointments(Request $request,Response $response) {

    $firebase_token = $request->getHeaderLine('Firebase_Token');
    $firebaseUid = getFirebaseUid($firebase_token);

    $user = getUserByFirebaseId($firebaseUid);

    //verify token
    if(!verify($firebase_token,$user))
        return $response->withStatus(403);

    $patients = Patient::where('loginid',$user)->get();

    $toReturn = array();
    foreach ($patients as $patient)
    {
        $appointments = Appointment::where('pid',$patient->id)->get();
        foreach ($appointments as $appointment)
        {
            $temp = array();
            $temp['id'] = $appointment->id;
            $temp['status'] = $appointment->status;
            $temp['date'] = $appointment->date;
            $temp_patient = Patient::find($appointment->pid);
            $temp['patient'] = $temp_patient->name;
            $temp_doctor = Doctor::find($appointment->did);
            $temp['doctor'] = $temp_doctor->name;
            $toReturn[] = $temp;
        }

    }

    return $response->getBody()->write(json_encode($toReturn));

}

function addAppointments(Request $request,Response $response) {
    //get token from header
    $firebase_token = $request->getHeaderLine('Firebase_Token');

    $appointment = json_decode( $request->getParsedBody() );
    $patient = $appointment['pid'];
    $user = getUser($patient);
    //verify token
    if(!verify($firebase_token,$user))
        return $response->withStatus(403);

    $doctor = $appointment['doctor'];
    $date = $appointment['date'];

    if(!availability($doctor,$date))
        return $response->withStatus(400)->write("Doctor not available on this date");

    //Appointment controller

    $newAppointment = new Appointment();
    $newAppointment->pid = $patient;
    $newAppointment->did = $doctor;
    $newAppointment->date = $date;
    $newAppointment->save();

    if($newAppointment->id>0)
    {
        //addEvent($user,$newAppointment);
        return $response->getBody()->write(json_encode($newAppointment->id));
    }
    else
        return $response->withStatus(500)->write("couldn't add appointment");

}

function getPatients(Request $request,Response $response) {

    $firebase_token = $request->getHeaderLine('Firebase_Token');

	$firebaseUid = getFirebaseUid($firebase_token);

    $user = getUserByFirebaseId($firebaseUid);


    //verify token
    if(!verify($firebase_token,$user))
        return $response->withStatus(403);

    $patients = Patient::where('loginid',$user)->get();

    return $response->getBody()->write($patients->toJSON());

}

function addPatient(Request $request,Response $response) {
    $firebase_token = $request->getHeaderLine('Firebase_Token');

    $firebaseUid = getFirebaseUid($firebase_token);

    $userId = getUserByFirebaseId($firebaseUid);

    $details = json_decode( $request->getBody() );

    $patient = new Patient();

    $patient->loginid = $userId;
    $patient->name = $details['name'];
    $patient->age = $details['age'];
    $patient->gender = $details['gender'];

    $patient->save();
    if($patient->id>0)
        return $response->getBody()->write($patient->id);
    else
        return $response->withStatus(500);
}

function register(Request $request,Response $response) {

    //get token from header
    $firebase_token = $request->getHeaderLine('Firebase_Token');

    $uid = getFirebaseUid($firebase_token);
    $profile = ( $request->getParsedBody() );


//return $response->getBody()->write(json_encode($profile));


    $phone = $profile['phone'];
	$phone = substr($phone,3);
    $address = $profile['address'];
    $age = $profile['age'];
    $gender = $profile['gender'];
    $name = $profile['name'];

	//return $response->withStatus(400);

    $email = getEmail($uid);

    $newUser = new User();
    $newUser->googleid = $uid;
    $newUser->phone = $phone;
    $newUser->email = $email;
    $newUser->address = $address;
    $newUser->save();
    $id = $newUser->id;

    if($id>0)
    {
        $newPatient = new Patient();
        $newPatient->loginid = $id;
        $newPatient->name = $name;
        $newPatient->age = $age;
        $newPatient->gender = $gender;
        $newPatient->save();

        $selfid = $newPatient->id;

        $newUser->self = $selfid;
        $newUser->save();

        return $response->getBody()->write(json_encode(array("userid"=>$id)));
    }

    return $response->withStatus(400);

}

function login(Request $request,Response $response) {

    $firebase_token = $request->getHeaderLine('Firebase_Token');

    $firebaseUid = getFirebaseUid($firebase_token);

    $userId = getUserByFirebaseId($firebaseUid);

    return $response->getBody()->write(json_encode(
        array("userid"=>$userId)
    ));

}

function profile(Request $request,Response $response,array $args) {
    $firebase_token = $request->getHeaderLine('Firebase_Token');
    $user = $args['userid'];

    //verify token
    if(!verify($firebase_token,$user))
        return $response->withStatus(403);

    $contact = User::find($user);
    $profile = Patient::find($contact->self);

    return $response->getBody()->write(
        json_encode(
            array($contact,$profile)
        )
    );

}

//For Android App

function doctors(Request $request,Response $response) {
	$doctors = Doctor::all();
	return $response->getBody()->write(json_encode($doctors));
}




// Additional functions

function verify($idToken,$userId){

    $localfirebaseId  = getFirebaseId($userId);
    $firebaseUid      = getFirebaseUid($idToken);

    if($firebaseUid == $localfirebaseId)
        return true;

    return false;

}

function getFirebaseId($id) {
    $fid = User::find($id);
    return $fid->googleid;

}

function getUser($pid) {
    return Patient::find($pid)->loginid;
}

function availability($doctor,$date) {
    $time = strtotime($date);
    $day = $date('D',$time);
    $num = dayToNum($day);

    $avail = Availability::where("did",$doctor);
    foreach ($avail as $a){
        if($num === $a->day)
            return true;
    }
    return false;
}

function dayToNum($day)
{
    $num = 0;
    switch ($day)
    {
        case "Sun":$num = 1;break;
        case "Mon":$num = 2;break;
        case "Tue":$num = 3;break;
        case "Wed":$num = 4;break;
        case "Thu":$num = 5;break;
        case "Fri":$num = 6;break;
        case "Sat":$num = 7;break;
    }
    return $num;
}

function getUserByFirebaseId($firebaseId) {
    $user =  User::where('googleid',$firebaseId)->get();
    if(!$user || count($user)<1)
        return 0;
    else
        return $user[0]->id;
}

// Firebase verification

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

function getFirebaseUid($idToken) {
    try{
        $serviceAccount = ServiceAccount::fromJsonFile(__DIR__ . '/../credentials/firebase_credentials.json');
        $apiKey = "API_KEY";
        $firebase = (new Factory)
            ->withServiceAccountAndApiKey($serviceAccount,$apiKey)
            ->create();

        $auth = $firebase->getAuth();
        $idToken = $auth->verifyIdToken($idToken);

        $uid = $idToken->getClaim('sub');

        $user = $auth->getUser($uid);

        $user->getEmail();

        return $uid;

    }catch (Exception $e){
        //echo $e->getMessage();
        //TODO
        // Testing
        return 0;
    }
}

function getEmail(string $uid) {
    try{
        $serviceAccount = ServiceAccount::fromJsonFile(__DIR__ . '/../credentials/firebase_credentials.json');
        $apiKey = "AIzaSyCG69fkPUn3YnV-0AvKRKcYx_KLs-idQnY";
        $firebase = (new Factory)
            ->withServiceAccountAndApiKey($serviceAccount,$apiKey)
            ->create();

        $auth = $firebase->getAuth();

        $user = $auth->getUser($uid);

        return $user->getEmail();

    }catch (Exception $e){return 0;}

}


function addEvent($user,$appointment) {

    $client = new Google_Client();
    $client->setApplicationName("CareHack");
    $client->setDeveloperKey("AIzaSyCG69fkPUn3YnV-0AvKRKcYx_KLs-idQnY");

    $service = new Google_Service_Calendar_Calendar();

    $email = User::find($user)->email;

    $event = new Google_Service_Calendar_Event(array(
        'summary' => 'No summary',
        'location' => 'Crystal Hospital',
        'description' => 'Appointment with Dr.'.Doctor::find($appointment->did)->name,
        'start' => array(
            'date' => $appointment->date,
            'timeZone' => 'America/Los_Angeles',
        ),
        'end' => array(
            'date' => $appointment->date,
            'timeZone' => 'America/Los_Angeles',
        ),
        'recurrence' => array(
            'RRULE:FREQ=DAILY;COUNT=2'
        ),
        'attendees' => array(
            array('email' => $email),
        ),
        'reminders' => array(
            'useDefault' => FALSE,
            'overrides' => array(
                array('method' => 'email', 'minutes' => 24 * 60),
                array('method' => 'popup', 'minutes' => 10),
            ),
        ),
    ));

    $calendarId = 'primary';
    $event = $service->events->insert($calendarId, $event);
    printf('Event created: %s\n', $event->htmlLink);

}

