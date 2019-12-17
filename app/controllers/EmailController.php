<?php
namespace App\Controllers;

use App\Helpers\ArrayHelper;
use App\Helpers\FilterHelper;
use App\Models\Email;

class EmailController extends ControllerBase
{
    /**
     * @Delete("/email/{id:[0-9]+}")
     * @param int $id
     * @throws \App\Exceptions\NotFoundException
     */
    public function deleteAction(int $id)
    {
        /** @var Email $email */
        $email = Email::findFirst($id);

        if (!$email) {
            throw new \App\Exceptions\NotFoundException(Email::class);
        }

        $email->delete();
    }

    /**
     * @Get("/email/find-by-id/{id:[0-9]+}")
     * @param int $id
     * @return Email
     * @throws \App\Exceptions\NotFoundException
     */
    public function findByIdAction(int $id)
    {
        /** @var Email $email */
        $email = Email::findFirst($id);

        if (!$email) {
            throw new \App\Exceptions\NotFoundException(Email::class);
        }

        return $email;
    }

    /**
     * @Get("/email/find-by-email/{email:.*}")
     * @param string $email
     * @return Email
     * @throws \App\Exceptions\NotFoundException
     */
    public function findByNumberAction(string $email)
    {
        $email = $this->checkAndFormatEmail($email);

        /** @var Email $email */
        $email = Email::findFirst([
            "email = :email:",
            "bind" => [
                "email" => $email
            ]
        ]);

        if (!$email) {
            throw new \App\Exceptions\NotFoundException(Email::class);
        }

        return $email;
    }

    /**
     * @Get("/email/delete-by-email/{email:.*}")
     * @param string $email
     * @return bool
     * @throws \App\Exceptions\NotFoundException
     */
    public function deleteByNumberAction(string $email)
    {
        $email = $this->checkAndFormatEmail($email);

        /** @var Email $email */
        $email = Email::findFirst([
            "email = :email:",
            "bind" => [
                "email" => $email
            ]
        ]);

        if ($email !== false) {
            if ($email->delete() === false) {

                $messages = $email->getMessages();
                $errorMsg = [];
                foreach ($messages as $message) {
                    $errorMsg[] = $message . "\n";
                }
                return false;
            } else {
                return true;
            }
        } else {
            throw new \App\Exceptions\NotFoundException(Email::class);
        }
    }


    /**
     * @Get("/email/add-by-email/{email:.*}")
     * @param string $email
     * @return bool
     */
    public function addByNumberAction(string $email)
    {
        if (empty($email)) {
            return false;
        }

        $email = $this->checkAndFormatEmail($email);

        $stackService = new \App\Services\StackService("emails", ["email"]);
        $stackService->noConflict = false;

        $stackService->begin();
        $stackService->add([$email]);
        $stackService->end();

        return true;
    }

    /**
     * @Post("/email")
     * @Put("/email/{id:[0-9]+}")
     * @param int|null $id
     * @return Email
     * @throws \App\Exceptions\MissingParameterException
     * @throws \App\Exceptions\ValidationFailedException
     * @throws \App\Exceptions\NotFoundException
     */
    public function editAction(int $id = null)
    {
        $body = $this->request->getJsonRawBody(true);

        if (!ArrayHelper::keyExists('email', $body)) {
            throw new \App\Exceptions\MissingParameterException("Email");
        }

        if ($this->request->isPut()) {
            /** @var Email $email */
            $email = Email::findFirst($id);

            if (!$email) {
                throw new \App\Exceptions\NotFoundException(Email::class);
            }
        } else {
            $email = new Email();
        }

        $email->email = FilterHelper::email($body['email']);

        if (!$email->save()) {
            throw new \App\Exceptions\ValidationFailedException($email->getMessages());
        }

        return $email;
    }

    /**
     * @param string $email
     * @return string
     */
    private function checkAndFormatEmail($email)
    {
        $email = FilterHelper::email($email);
        $email = FilterHelper::trim($email);
        $email = mb_strtoupper($email);
        $email = md5($email);
        return mb_strtoupper($email);
    }
}