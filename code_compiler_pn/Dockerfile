# Use the official PHP 8.2 image as a base image
FROM php:8.2-apache

# Install Python and Node.js
RUN apt-get update     
RUN apt-get install -y python3 python3-pip     
RUN curl -sL https://deb.nodesource.com/setup_14.x | bash -    
RUN apt-get install -y nodejs
RUN apt-get install -y maven 

# Install Java
RUN apt-get install -y default-jdk

# Install C#
RUN apt-get install -y wget
RUN wget https://packages.microsoft.com/config/debian/10/packages-microsoft-prod.deb -O packages-microsoft-prod.deb 
RUN dpkg -i packages-microsoft-prod.deb 
RUN apt-get update 
RUN apt-get install -y dotnet-sdk-6.0


# Copy custom PHP configuration file
COPY php.ini /usr/local/etc/php/

# Copy the application code to the container
COPY src/ /var/www/html/

# Create a directory for logs and set permissions
RUN mkdir -p /var/log/code && chmod -R 777 /var/log/code

# Expose port 80
EXPOSE 80
