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
        // case 'javascript':
        //     $code = $code .= "function main() {
        //                 var result = createValue();
        //                 console.log(result);
        //             }

        //             main();";
        //     $command = "node " . escapeshellarg($scriptFile) . " 2>&1";
        //     file_put_contents($scriptFile, $code);
        //     break;
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
            // $className = 'Main';
            // $command = "cd " . escapeshellarg(sys_get_temp_dir()) . " && javac " . escapeshellarg($javaFile) . " 2> /var/log/code/error.log && java -cp " . escapeshellarg(sys_get_temp_dir()) . " $className 2>> /var/log/code/error.log";
            $command = "cd " . escapeshellarg(sys_get_temp_dir()) . " && java " . 
            " $javaFile 2>> /var/log/code/error.log";
            
            break;
        // case 'c':
        //     $x = '#include <stdio.h>
        //             ';

        //     $y= 'int main() {
        //                 int size;
        //                 int* result = (int*) createValue(&size);
        //                 for (int i = 0; i < size; i++) {
        //                     printf("%d ", result[i]);
        //                 }
        //                 return 0;
        //             }
        //             ';
        //     $code = $x . $code . $y;
        //     $cFile = sys_get_temp_dir() . "/exec.c";
        //     file_put_contents($cFile, $code);
        //     $outputFile = sys_get_temp_dir() . "/exec.out";
        //     $command = "gcc " . escapeshellarg($cFile) . " -o " . escapeshellarg($outputFile) . " && " . escapeshellarg($outputFile) . " 2>&1";
        //     break;
        // case 'cpp':
        //     $x = '#include <iostream>
        //     #include <stdio.h>
        //     ';


        //     // Main function
        //     $y = 'int main() {
        //         int size;
        //         // Call the function and store the returned array
        //         int* myArray = createArray(size);

        //         for (int i = 0; i < size; i++) {
        //             std::cout << myArray[i] << " ";
        //         }
        //         std::cout << std::endl;

        //         // Free the allocated memory
        //         delete[] myArray;

        //         return 0;
        //     }';

        //     // Concatenate all parts to form the complete C++ code
        //     $code = $x . $code . $y;
        //     $cppFile = sys_get_temp_dir() . "/exec.cpp";
        //     file_put_contents($cppFile, $code);
        //     $outputFile = sys_get_temp_dir() . "/exec.out";
        //     $command = "g++ " . escapeshellarg($cppFile) . " -o " . escapeshellarg($outputFile) . " && " . escapeshellarg($outputFile) . " 2>&1";
        //     break;
        // case 'php':
        //     $code = $code ."function main() {
        //                 \$result = createValue();
        //                 print_r(\$result);
        //             }

        //             main();";
        //     if (stripos($code, '<?php') === false) {
        //         $code = "<?php\n" . $code;
        //     }
        //     file_put_contents($scriptFile, $code);
        //     $command = "php " . escapeshellarg($scriptFile) . " 2>&1";
        //     break;
        default:
            return ["error" => "Unsupported code type: $type"];
    }

    exec($command, $output, $returnCode);

    if ($type === 'java') {
        unlink($javaFile);
    } 
    // elseif ($type === 'c' || $type === 'cpp') {
    //     unlink($cFile ?? $cppFile);
    //     unlink($outputFile);
    // } 
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

    // if ($output === $expected) {
    //     echo json_encode(["status" => "Testcase passed", "output" => $output]);
    // } else {
    //     echo json_encode(["status" => "Testcase failed", "output" => $output]);
    // }
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
