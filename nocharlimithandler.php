<?php
date_default_timezone_set('America/Phoenix');

require __DIR__ . '/vendor/autoload.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

// Function to print the receipt
function printReceipt($name, $text)
{
    try {
        // Validate input length after trimming
        if (strlen($name) > 42) {
            throw new InvalidArgumentException('Input exceeds character limit. Stop trying to hack me :(');
        }

        $connector = new FilePrintConnector("/dev/usb/lp0");
        $printer = new Printer($connector);
        $timestamp = date('Y-m-d H:i:s');

        // Print the person's name
        $printer->setEmphasis(true);
        $printer->text("Name: ");
        $printer->text($name);
        $printer->text("\n\n");
        $printer->setEmphasis(false);

        // Print the user input
        $printer->text("Message: ");
        $printer->text($text);
        $printer->text("\n\n");

        // Print the time
        $printer->text("Time and date: ");
        $printer->text($timestamp);
        $printer->text("\n");

        // Cut the paper
        $printer->cut(Printer::CUT_PARTIAL);

        // Close the printer
        $printer->close();

        echo "Message has been printed!";
        logMessage($name, $text);
    } catch (\InvalidArgumentException $e) {
        // Case 1: The text submitted is too long
        echo 'Error: ' . $e->getMessage();
        // Log the error to a file
        logError($name, $text, $e->getMessage());
        logMessage($name, $text, false);
    } catch (\Exception $e) {
        // Case 2: Printer cannot be initialized
        if ($e->getMessage() === 'Cannot initialise FilePrintConnector.') {
            echo 'Error: Printer is unplugged or off, try again later :(';
            // Log the error to a file
            logError($name, $text, $e->getMessage());
            logMessage($name, $text, false);
        } else {
            // Case 3: Other exceptions
            echo 'Error: ' . $e->getMessage();
            // Log the error to a file
            logError($name, $text, $e->getMessage());
            logMessage($name, $text, false);
        }
    }
    
}

// Error logging
function logError($name, $message, $errorMessage)
{
    $timestamp = date('Y-m-d H:i:s');
    $ipAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'UNKNOWN';
    $logMessage = "[$timestamp] Error: $errorMessage\n";
    
    // Log the error to a file
    error_log($logMessage, 3, 'error.log');
}

function logMessage($name, $message, $success = true)
{
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] User: $name - Msg: $message - " . ($success ? 'Success' : 'Error') . "\n";

    error_log($logMessage, 3, 'messages.log');
}


// Get and sanitize user input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userName = isset($_POST['user_name']) ? trim($_POST['user_name']) : '';
    $userInput = isset($_POST['user_input']) ? trim($_POST['user_input']) : '';

    // Log message & Print the receipt
    printReceipt($userName, $userInput);
}

