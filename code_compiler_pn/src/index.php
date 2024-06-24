<?php
// Function to execute code based on type
function executeCode($code, $type) {
    // Generate a unique file name for the temporary script
    $scriptFile = tempnam(sys_get_temp_dir(), 'exec');

    // Determine the command based on the type of code
    switch ($type) {
        case 'python':
            $command = "python3 " . escapeshellarg($scriptFile) . " 2> /var/log/code/error.log";
            file_put_contents($scriptFile, $code);
            break;
        case 'node':
            $command = "node " . escapeshellarg($scriptFile) . " 2> /var/log/code/error.log";
            file_put_contents($scriptFile, $code);
            break;
        case 'java':
            $javaFile = sys_get_temp_dir() . "/ExecClass.java";
            file_put_contents($javaFile, $code);
            $className = 'ExecClass';
            $command = "cd " . escapeshellarg(sys_get_temp_dir()) . " && javac " . escapeshellarg($javaFile) . " 2> /var/log/code/error.log && java -cp " . escapeshellarg(sys_get_temp_dir()) . " $className 2>> /var/log/code/error.log";
            break;
        case 'c':
            $cFile = sys_get_temp_dir() . "/exec.c";
            file_put_contents($cFile, $code);
            $outputFile = sys_get_temp_dir() . "/exec.out";
            $command = "gcc " . escapeshellarg($cFile) . " -o " . escapeshellarg($outputFile) . " 2> /var/log/code/error.log && " . escapeshellarg($outputFile) . " 2>> /var/log/code/error.log";
            break;
        case 'cpp':
            $cppFile = sys_get_temp_dir() . "/exec.cpp";
            file_put_contents($cppFile, $code);
            $outputFile = sys_get_temp_dir() . "/exec.out";
            $command = "g++ " . escapeshellarg($cppFile) . " -o " . escapeshellarg($outputFile) . " 2> /var/log/code/error.log && " . escapeshellarg($outputFile) . " 2>> /var/log/code/error.log";
            break;
        case 'php':
            $command = "php " . escapeshellarg($scriptFile) . " 2> /var/log/code/error.log";
            file_put_contents($scriptFile, "<?php\n" . $code);
            break;
        default:
            return "Unsupported code type: $type";
    }

    // Execute the command and capture the output and return code
    exec($command, $output, $returnCode);

    // Remove the temporary script file
    if ($type === 'java') {
        unlink($javaFile);
        unlink(sys_get_temp_dir() . "/ExecClass.class");
    } elseif ($type === 'c' || $type === 'cpp') {
        unlink($cFile ?? $cppFile);
        unlink($outputFile);
    } else {
        unlink($scriptFile);
    }

    // If the return code is non-zero, log the error and return it
    if ($returnCode !== 0) {
        $errorLog = file_get_contents('/var/log/code/error.log');
        return "Error Log: <br>" . nl2br($errorLog);
    }

    // Return the output
    return implode("\n", $output);
}

// Example Python code execution
$pythonCode = '
for i in range(5):
    print(f"Python iteration: {i}")

# Uncomment the following line to cause an error
# raise ValueError("This is a test error")
';

$pythonResult = executeCode($pythonCode, 'python');

// Example Node.js code execution
$nodeCode = '
for (let i = 0; i < 5; i++) {
    console.log(i);
}

// Uncomment the following line to cause an error
// throw new Error("This is a test error");
';

$nodeResult = executeCode($nodeCode, 'node');

// Example Java code execution
$javaCode = '
public class ExecClass {
    public static void main(String[] args) {
        for (int i = 0; i < 5; i++) {
            System.out.println("Java iteration: " + i);
        }

        // Uncomment the following line to cause an error
        // throw new RuntimeException("This is a test error");
    }
}
';

$javaResult = executeCode($javaCode, 'java');

// Example C code execution
$cCode = '
#include <stdio.h>

int main() {
    for (int i = 0; i < 5; i++) {
        printf("C iteration: %d\\n", i);
    }

    // Uncomment the following line to cause an error
    // return 1 / 0;

    return 0;
}
';

$cResult = executeCode($cCode, 'c');

// Example C++ code execution
$cppCode = '
#include <iostream>

int main() {
    for (int i = 0; i < 5; i++) {
        std::cout << "C++ iteration: " << i << std::endl;
    }

    // Uncomment the following line to cause an error
    // throw std::runtime_error("This is a test error");

    return 0;
}
';

$cppResult = executeCode($cppCode, 'cpp');

// Example PHP code execution
$phpCode = '
for ($i = 0; $i < 5; $i++) {
    echo "PHP iteration: $i\\n";
}

// Uncomment the following line to cause an error
// throw new Exception("This is a test error");
';

$phpResult = executeCode($phpCode, 'php');

// Output the results
echo "Python Result:<br>" . nl2br($pythonResult) . "<br><br>";
echo "Node.js Result:<br>" . nl2br($nodeResult) . "<br><br>";
echo "Java Result:<br>" . nl2br($javaResult) . "<br><br>";
echo "C Result:<br>" . nl2br($cResult) . "<br><br>";
echo "C++ Result:<br>" . nl2br($cppResult) . "<br><br>";
echo "PHP Result:<br>" . nl2br($phpResult) . "<br><br>";
?>
