<?php

namespace App\Helper\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Annotation\Groups;

class ApiException extends HttpException
{
    #[Groups(['show'])]
    protected $message;

    #[Groups(['show'])]
    protected string $detail;

    #[Groups(['show'])]
    protected int $status;

    #[Groups(['show'])]
    protected array $validationArray;

    /**
     * @param string $message
     * @param string $detail
     * @param int $status
     * @param array $validationArray
     * @param HttpException|null $previous
     * @param array $headers
     * @param int $code
     */
    public function __construct(
        string        $message = '',
        string        $detail = '',
        int           $status = Response::HTTP_BAD_REQUEST,
        array         $validationArray = ['query' => [], 'body' => []],
        HttpException $previous = null,
        array         $headers = [],
        int           $code = 0,
    )
    {
        $this->message = $message;
        $this->detail = $detail;
        $this->status = $status;
        $this->validationArray = $validationArray;
        parent::__construct($status, $message, $previous, $headers, $code);
    }

    public function response(): array
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'detail' => $this->detail,
            'validationError' => $this->validationArray,
        ];
    }
}