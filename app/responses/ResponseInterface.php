<?php
namespace App\Responses;

interface ResponseInterface
{
    /**
     * @param mixed $result
     * @param bool $error
     * @return string
     */
    public function send($result, $error = false);
}