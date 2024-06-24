<?php

header('Content-Type: application/json');

// Function to execute code based on type
function executeCode($code, $type) {
    $scriptFile = tempnam(sys_get_temp_dir(), 'exec');
    
    switch ($type) {
        case 'python':
            $command = "python3 " . escapeshellarg($scriptFile) . " 2>&1";
            file_put_contents($scriptFile, $code);
            break;
        case 'node':
            $command = "node " . escapeshellarg($scriptFile) . " 2>&1";
            file_put_contents($scriptFile, $code);
            break;
        case 'java':
            $javaFile = sys_get_temp_dir() . "/ExecClass.java";
            file_put_contents($javaFile, $code);
            $className = 'ExecClass';
            $command = "cd " . escapeshellarg(sys_get_temp_dir()) . " && javac " . escapeshellarg($javaFile) . " && java -cp " . escapeshellarg(sys_get_temp_dir()) . " $className 2>&1";
            break;
        case 'c':
            $cFile = sys_get_temp_dir() . "/exec.c";
            file_put_contents($cFile, $code);
            $outputFile = sys_get_temp_dir() . "/exec.out";
            $command = "gcc " . escapeshellarg($cFile) . " -o " . escapeshellarg($outputFile) . " && " . escapeshellarg($outputFile) . " 2>&1";
            break;
        case 'cpp':
            $cppFile = sys_get_temp_dir() . "/exec.cpp";
            file_put_contents($cppFile, $code);
            $outputFile = sys_get_temp_dir() . "/exec.out";
            $command = "g++ " . escapeshellarg($cppFile) . " -o " . escapeshellarg($outputFile) . " && " . escapeshellarg($outputFile) . " 2>&1";
            break;
        case 'php':
            $command = "php " . escapeshellarg($scriptFile) . " 2>&1";
            file_put_contents($scriptFile, "<?php\n" . $code);
            break;
        default:
            return ["error" => "Unsupported code type: $type"];
    }

    exec($command, $output, $returnCode);

    if ($type === 'java') {
        unlink($javaFile);
        unlink(sys_get_temp_dir() . "/ExecClass.class");
    } elseif ($type === 'c' || $type === 'cpp') {
        unlink($cFile ?? $cppFile);
        unlink($outputFile);
    } else {
        unlink($scriptFile);
    }

    return ["output" => implode("\n", $output), "returnCode" => $returnCode];
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

    $output = trim($result['output']);
    $expected = trim($expected);

    if ($output === $expected) {
        echo json_encode(["status" => "Testcase passed", "output" => $output]);
    } else {
        echo json_encode(["status" => "Testcase failed", "output" => $output]);
    }
}

// Route the request to the appropriate handler based on the URL
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod === 'POST') {
    // create an array of languages that are supported
    $supportedLanguages = ['python', 'node', 'java', 'c', 'cpp', 'php'];
    
    // check if the request body contains type as one of the supported languages
    $input = getJsonInput();
    if (!$input || !isset($input['type']) || !in_array($input['type'], $supportedLanguages)) {
        echo json_encode(["error" => "Invalid input"]);
        return;
    }
}
else {
    echo json_encode(["error" => "Invalid request method"]);
}
