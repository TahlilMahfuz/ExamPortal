#!/bin/bash

# Create the project directory and subdirectories
mkdir -p code_compiler_pn/src
mkdir -p code_compiler_pn/logs

# Create the Dockerfile for PHP with Python and Node.js installed
cat <<EOL > code_compiler_pn/Dockerfile
# Use the official PHP 8.2 image as a base image
FROM php:8.2-apache

# Install Python and Node.js
RUN apt-get update \
    && apt-get install -y python3 python3-pip \
    && curl -sL https://deb.nodesource.com/setup_14.x | bash - \
    && apt-get install -y nodejs

# Copy custom PHP configuration file
COPY php.ini /usr/local/etc/php/

# Copy the application code to the container
COPY src/ /var/www/html/

# Create a directory for logs and set permissions
RUN mkdir -p /var/log/code && chmod -R 777 /var/log/code

# Expose port 80
EXPOSE 80
EOL

# Create the php.ini file
cat <<EOL > code_compiler_pn/php.ini
display_errors = On
error_reporting = E_ALL
EOL

# Create the index.php file
cat <<EOL > code_compiler_pn/src/index.php
<?php
// Function to execute code based on type
function executeCode(\$code, \$type) {
    // Generate a unique file name for the temporary script
    \$scriptFile = tempnam(sys_get_temp_dir(), 'exec');

    // Determine the command based on the type of code
    switch (\$type) {
        case 'python':
            \$command = "python3 " . escapeshellarg(\$scriptFile) . " 2> /var/log/code/error.log";
            break;
        case 'node':
            \$command = "node " . escapeshellarg(\$scriptFile) . " 2> /var/log/code/error.log";
            break;
        default:
            return "Unsupported code type: \$type";
    }

    // Write the code to the temporary script file
    file_put_contents(\$scriptFile, \$code);

    // Execute the command and capture the output and return code
    exec(\$command, \$output, \$returnCode);

    // Remove the temporary script file
    unlink(\$scriptFile);

    // If the return code is non-zero, log the error and return it
    if (\$returnCode !== 0) {
        \$errorLog = file_get_contents('/var/log/code/error.log');
        return "Error Log: <br>" . nl2br(\$errorLog);
    }

    // Return the output
    return implode("\\n", \$output);
}

// Example Python code execution
\$pythonCode = '
for i in range(5):
    print(f"Python iteration: {i}")

# Uncomment the following line to cause an error
# raise ValueError("This is a test error")
';

\$pythonResult = executeCode(\$pythonCode, 'python');

// Example Node.js code execution
\$nodeCode = '
for (let i = 0; i < 5; i++) {
    console.log(`Node.js iteration: \${i}`);
}

// Uncomment the following line to cause an error
// throw new Error("This is a test error");
';

\$nodeResult = executeCode(\$nodeCode, 'node');

// Output the results
echo "Python Result:<br>" . nl2br(\$pythonResult) . "<br><br>";
echo "Node.js Result:<br>" . nl2br(\$nodeResult);
?>
EOL

# Create the docker-compose.yml file
cat <<EOL > code_compiler_pn/docker-compose.yml
version: '3.8'

services:
  web:
    build: .
    ports:
      - "80:80"
    volumes:
      - ./src:/var/www/html

EOL

# Set execute permissions for PHP script and Dockerfile
chmod +x code_compiler_pn/src/index.php
chmod +x code_compiler_pn/Dockerfile

echo "Project setup completed successfully!"
