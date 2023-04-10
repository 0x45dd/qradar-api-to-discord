<?php

// Set up the API endpoint URL and credentials
$apiEndpoint = "https://qradar.target.com/api/siem/offenses";
$apiKey = "API KEY";
$headers = array("Accept: application/json", "SEC: {$apiKey}");

// Construct the query string to retrieve the latest 5 offenses
$queryString = "?fields=id,description&filter=id>9090";
// Make a cURL request to the QRadar API endpoint and save the response

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiEndpoint . $queryString);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

// Decode the JSON response into a PHP array
$responseArray = json_decode($response, true);

// Loop through the offenses and save the ID and description to a file

$fp = fopen('offenses.txt', 'w');
foreach ($responseArray as $offense) {
    fwrite($fp, $offense['id'] . " - " . $offense['description'] . "

");
}
fclose($fp);

// get latest id from file

$lines = file('offenses.txt');
$last_id = 0;
foreach ($lines as $line) {
    $id = explode(" - ", $line);
    if ($id[0] > $last_id) {
        $last_id = $id[0];
    }
    print_r($id);
}

unlink('offenses.txt');
// save latest id to file

$fp = fopen('last_id.txt', 'w');
fwrite($fp, $last_id);
fclose($fp);

$real_id = "id=$last_id";

// read last_id.txt and asif.last_id.txt if they are the same then do nothing if not then send to discord

$last_id = file_get_contents('last_id.txt');
$asif_last_id = file_get_contents('asif.last_id.txt');

if ($last_id == $asif_last_id) {
    echo "same";
    system('rm asif.last_id.txt');
    system('mv last_id.txt asif.last_id.txt');

} else {

// get everything from last id

$queryString1 = "?fields=id,description,severity,offense_source,status,categories,event_count,rules,log_sources&filter=$real_id";

$ch1 = curl_init();
curl_setopt($ch1, CURLOPT_URL, $apiEndpoint . $queryString1);
curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
$response1 = curl_exec($ch1);
curl_close($ch1);

//read response  and grep for description, offense_source, status, categories, event_count, rules, log_sources, severity, id

$description = explode("description\":\"", $response1);
$description = explode("\",\"", $description[1]);
$description = $description[0];

$offense_source = explode("offense_source\":\"", $response1);
$offense_source = explode("\",\"", $offense_source[1]);
$offense_source = $offense_source[0];

print_r($offense_source);


$status = explode("status\":\"", $response1);
$status = explode("\",\"", $status[1]);
$status = $status[0];

print_r($status);

$categories = explode("categories\":[\"", $response1);
$categories = explode("\",\"", $categories[1]);
$categories = $categories[0];

$event_count = explode("event_count\":", $response1);
$event_count = explode(",", $event_count[1]);
$event_count = $event_count[0];

$rules = explode("rules\":[\"", $response1);
$rules = explode("\",\"", $rules[1]);
$rules = $rules[1];

$log_sources = explode("log_sources\":[\"", $response1);
$log_sources = explode("\",\"", $log_sources[1]);
$log_sources = $log_sources[0];

$severity = explode("severity\":", $response1);
$severity = explode(",", $severity[1]);
$severity = $severity[0];

$id = explode("id\":", $response1);
$id = explode(",", $id[1]);
$id = $id[0];


// send all variables to discord as content of webhook

$webhook_url = 'webhook_url'; // Replace with your own webhook URL

// Set up the file path to the file you want to send



$text = "New Offense:";

// Set up the data to send to the webhook
$data = array(
    'content' => "\n**Offense Description:**$description \n**Offense Source:**$offense_source \n**ID:**$id \n**STATUS:**$status \n**Severity:**$severity\n\n\t\r\n\r\n\r\n"
);

// Set up the options for the HTTP request
$options = array(
    'http' => array(
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
    ),
);

// Send the HTTP request to the webhook URL
$context  = stream_context_create($options);
$result = file_get_contents($webhook_url, false, $context);

// Print the response from the webhook
echo $result;

system('mv last_id.txt asif.last_id.txt');

}

file_get_contents('last_id.txt');

?>