<?php
require '../../../autoload.php';
require 'cfx_bootstrap.php';

$method = new ReflectionMethod('Script', $_POST['type']);
$return = $method->invokeArgs(new Script(), array($_POST));

echo json_encode($return);

