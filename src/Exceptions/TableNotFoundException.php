<?php
namespace Ichikawayac\ReverseScaffoldGenerator\Exceptions;

use Exception;

class TableNotFoundException extends Exception {
    protected $message = 'Table not found in the database.';
}
