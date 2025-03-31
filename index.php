<?php


// Database configuration
$host = 'localhost'; // Replace with your database host
$dbname = 'uk_address'; // Replace with your database name
$username = 'root'; // Replace with your database username
$password = ''; // Replace with your database password

// API key for getAddress.io
$apikey = 'CRzyS9hst0yt1Ad9ovEZXw45617'; // Replace with your actual API key

// Function to fetch address data from getAddress.io API
function get_address($postcode, $apikey)
{
    $curl = curl_init();
    $postcode = str_replace(' ', '', strtoupper($postcode)); // Normalize postcode
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.getaddress.io/autocomplete/$postcode?api-key=$apikey&all=all",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Cookie: TiPMix=28.816803406515067; x-ms-routing-name=self'
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    $response = json_decode($response, true);

    $postcodeArray = array();
    if (isset($response['suggestions'])) {
        foreach ($response['suggestions'] as $key => $value) {
            if (isset($value['address'])) {
                $postcodeArray[$postcode][] = $value['address'];
            }
        }
        return $postcodeArray;
    } else {
        return $postcodeArray[$postcode] = []; // Return empty array if no suggestions found
    }
}

// Function to connect to the database and insert data using MySQLi
function insert_postcode_address($postcode, $address_list, $conn)
{
    // Prepare the JSON data
    $address_list_json = json_encode($address_list);

    // Escape strings to prevent SQL injection
    $postcode = mysqli_real_escape_string($conn, $postcode);
    $address_list_json = mysqli_real_escape_string($conn, $address_list_json);

    // SQL query to insert data
    $sql = "INSERT INTO `postcode_address` (`postcode`, `address_list_json`) VALUES ('$postcode', '$address_list_json')";

    // Execute the query
    if (mysqli_query($conn, $sql)) {
        echo "Data inserted successfully for postcode: $postcode\n";
    } else {
        echo "Error inserting data: " . mysqli_error($conn) . "\n";
    }
}

// Main script
// Establish database connection using MySQLi
$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error() . "\n");
}

// Read postcodes from CSV file
$csvFile = 'postcode.csv'; // Path to your CSV file
if (($handle = fopen($csvFile, 'r')) !== false) {
    // Skip header row if it exists (adjust if your CSV has no header)
    $header = fgetcsv($handle);

    while (($data = fgetcsv($handle)) !== false) {
        $postcode = trim($data[0]); // Assuming postcode is in the first column
        
        // Fetch address data
        $address_data = get_address($postcode, $apikey);
        if (!empty($address_data)) {
            // Insert into database
            insert_postcode_address($postcode, $address_data[$postcode], $conn);
        } else {
            echo "No address data found for postcode: $postcode\n";
        }
    }
    fclose($handle);
} else {
    echo "Could not open CSV file.\n";
}

// Close the database connection
mysqli_close($conn);

// Example usage with a single postcode (for testing)
// $postcode = 'NW43TA';
// $get_address = get_address($postcode, $apikey);
// echo json_encode($get_address, JSON_PRETTY_PRINT) . "\n";
// echo '<pre>';
// print_r($get_address);
// $conn = mysqli_connect($host, $username, $password, $dbname);
// insert_postcode_address($postcode, $get_address[$postcode], $conn);
// mysqli_close($conn);

//============================================================================
// function get_address($postcode)
// {
//     $curl = curl_init();
//     $postcode = str_replace(' ', '', strtoupper($postcode));
//     curl_setopt_array($curl, array(
//         CURLOPT_URL => "https://api.getaddress.io/autocomplete/$postcode?api-key=$apikey&all=all",
//         CURLOPT_RETURNTRANSFER => true,
//         CURLOPT_ENCODING => '',
//         CURLOPT_MAXREDIRS => 10,
//         CURLOPT_TIMEOUT => 0,
//         CURLOPT_FOLLOWLOCATION => true,
//         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//         CURLOPT_CUSTOMREQUEST => 'GET',
//         CURLOPT_HTTPHEADER => array(
//             'Cookie: TiPMix=28.816803406515067; x-ms-routing-name=self'
//         ),
//     ));

//     $response = curl_exec($curl);

//     curl_close($curl);
//     $response = json_decode($response, true);

//     $postcodeArray = array();
//     if (isset($response['suggestions'])) {
//         foreach ($response['suggestions'] as $key => $value) {
//             if (isset($value['address'])) {
//                 $postcodeArray[$postcode][] = $value['address'];
//             }
//         }
//         return $postcodeArray;
//     } else {
//         return $postcodeArray[$postcode];
//     }
// }

// INSERT INTO `postcode_address`(`id`, `postcode`, `address_list_json`) VALUES ('[value-1]','[value-2]','[value-3]')

// $get_address = get_address('NW43TA');
// echo json_encode($get_address, JSON_PRETTY_PRINT);
// echo '<pre>';
// print_r($get_address);
// exit;