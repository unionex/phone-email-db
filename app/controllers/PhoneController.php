<?php
namespace App\Controllers;

use App\Helpers\ArrayHelper;
use App\Helpers\FilterHelper;
use App\Models\Phone;

class PhoneController extends ControllerBase
{
    /**
     * @Delete("/phone/{id:[0-9]+}")
     * @param int $id
     * @throws \App\Exceptions\NotFoundException
     */
    public function deleteAction(int $id)
    {
        /** @var Phone $phone */
        $phone = Phone::findFirst($id);

        if (!$phone) {
            throw new \App\Exceptions\NotFoundException(Phone::class);
        }

        $phone->delete();
    }

    /**
     * @Get("/phone/find-by-id/{id:[0-9]+}")
     * @param int $id
     * @return Phone
     * @throws \App\Exceptions\NotFoundException
     */
    public function findByIdAction(int $id)
    {
        /** @var Phone $phone */
        $phone = Phone::findFirst($id);

        if (!$phone) {
            throw new \App\Exceptions\NotFoundException(Phone::class);
        }

        return $phone;
    }

    /**
     * @Get("/phone/find-by-number/{phoneNumber:.*}")
     * @param string $phoneNumber
     * @return Phone
     * @throws \App\Exceptions\NotFoundException
     */
    public function findByNumberAction(string $phoneNumber)
    {
        $phoneNumber = $this->checkAndFormatPhone($phoneNumber);

        /** @var Phone $phone */
        $phone = Phone::findFirst([
            "number = :number:",
            "bind" => [
                "number" => $phoneNumber
            ]
        ]);

        if (!$phone) {
            throw new \App\Exceptions\NotFoundException(Phone::class);
        }

        return $phone;
    }

    /**
     * @Get("/phone/delete-by-number/{phoneNumber:.*}")
     * @param string $phoneNumber
     * @return bool
     * @throws \App\Exceptions\NotFoundException
     */
    public function deleteByNumberAction(string $phoneNumber)
    {
        $phoneNumber = $this->checkAndFormatPhone($phoneNumber);

        /** @var Phone $phone */
        $phone = Phone::findFirst([
            "number = :number:",
            "bind" => [
                "number" => $phoneNumber
            ]
        ]);

        if ($phone !== false) {
            if ($phone->delete() === false) {

                $messages = $phone->getMessages();
                $errorMsg = [];
                foreach ($messages as $message) {
                    $errorMsg[] = $message . "\n";
                }
                return false;
            } else {
                return true;
            }
        } else {
            throw new \App\Exceptions\NotFoundException(Phone::class);
        }
    }

    /**
     * @Get("/phone/add-by-number/{phone:.*}")
     * @param string $phone
     * @return bool
     */
    public function addByNumberAction(string $phone)
    {
        if (empty($phone)) {
            return false;
        }

        $phone = $this->checkAndFormatPhone($phone);

        $stackService = new \App\Services\StackService("phones", ["number"]);
        $stackService->noConflict = false;

        $stackService->begin();
        $stackService->add([$phone]);
        $stackService->end();

        return true;
    }

    /**
     * @Post("/phone")
     * @Put("/phone/{id:[0-9]+}")
     * @param int|null $id
     * @return Phone
     * @throws \App\Exceptions\MissingParameterException
     * @throws \App\Exceptions\ValidationFailedException
     * @throws \App\Exceptions\NotFoundException
     */
    public function editAction(int $id = null)
    {
        $body = $this->request->getJsonRawBody(true);

        if (!ArrayHelper::keyExists('phone', $body)) {
            throw new \App\Exceptions\MissingParameterException("Phone");
        }

        $phoneNumber = FilterHelper::phone($body['phone']);

        if ($this->request->isPut()) {
            /** @var Phone $phone */
            $phone = Phone::findFirst($id);

            if (!$phone) {
                throw new \App\Exceptions\NotFoundException(Phone::class);
            }
        } else {
            $phone = new Phone();
        }

        $phone->number = $phoneNumber;

        if (!$phone->save()) {
            throw new \App\Exceptions\ValidationFailedException($phone->getMessages());
        }

        return $phone;
    }

    /**
     * @param string $phone
     * @return string
     */
    private function checkAndFormatPhone($phone)
    {
        $phone = FilterHelper::email($phone);
        $phone = FilterHelper::trim($phone);
        $phone = md5($phone);
        return mb_strtoupper($phone);
    }
}