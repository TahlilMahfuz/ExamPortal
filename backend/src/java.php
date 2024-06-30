<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: token, Content-Type');
header('Access-Control-Max-Age: 1728000');
header('Content-Length: 0');
header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Function to execute code based on type
function executeCode($code, $type) {
    // print the code and type
    $scriptFile = tempnam(sys_get_temp_dir(), 'exec');
    
    switch ($type) {
        case 'java':
            $code = "import java.util.*;
                    import org.json.*;
                    public class Main {
                        public static void main(String[] args) {
                            Solution solution = new Solution();
                            Object result = solution.createValue();
                            System.out.println(new JSONObject().put(\"result\", result));
                        }
                    }".$code;
            $javaFile = sys_get_temp_dir() . "/Main.java";
            file_put_contents($javaFile, $code);
            $command = "cd " . escapeshellarg(sys_get_temp_dir()) . " && java " . 
            " $javaFile 2>> /var/log/code/error.log";
            
            break;
        default:
            return ["error" => "Unsupported code type: $type"];
    }

    exec($command, $output, $returnCode);

    if ($type === 'java') {
        unlink($javaFile);
    }  
    else {
        unlink($scriptFile);
    }

    // If the return code is non-zero, log the error and return it
    if ($returnCode === 0) {
        $jsonOutput = implode("\n", $output);
        $arrayResult = json_decode($jsonOutput, true);
        
        return ["output"=>$arrayResult];
    }
    else{
        $errorLog = file_get_contents('/var/log/code/error.log');
        return "Error Log: <br>" . nl2br($errorLog);
    }
}

// Helper function to get JSON input
function getJsonInput() {
    return json_decode(file_get_contents('php://input'), true);
}

// Main API handler
function handleRequest() {
    $input = getJsonInput();
    if (!$input || !isset($input['code']) || !isset($input['type']) || !isset($input['expected'])) {
        echo json_encode(["error" => "Invalid input"]);
        return;
    }

    $code = $input['code'];
    $type = $input['type'];
    $expected = $input['expected'];

    $result = executeCode($code, $type);
    
    if (isset($result['error'])) {
        echo json_encode($result);
        return;
    }

    $output = $result['output'];
    $expected = trim($expected);
    echo json_encode(["output" => $output]);
}

// Route the request to the appropriate handler based on the URL
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod === 'POST') {
    // Create an array of languages that are supported
    $supportedLanguages = ['python', 'javascript', 'java', 'c', 'cpp', 'php'];
    
    // Check if the request body contains type as one of the supported languages
    $input = getJsonInput();
    if (!$input || !isset($input['type']) || !in_array($input['type'], $supportedLanguages)) {
        echo json_encode(["error" => "Invalid language"]);
        return;
    } else {
        handleRequest();
    }
} else {
    echo json_encode(["error" => "Invalid request method"]);
}
