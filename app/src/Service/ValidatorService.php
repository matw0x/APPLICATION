<?php
namespace App\Service;
use App\Helper\Exception\ApiException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidatorService
{
    public function __construct(
        protected ValidatorInterface $validator,
    )
    {
    }
    public function validate($body = [], $groupsBody = [], $query = [], $groupsQuery = []): void
    {
        $bodyErrors = $this->validator->validate($body, groups: $groupsBody);
        $validationErrors = [
            'body' => [],
            'query' => [],
        ];
        foreach ($bodyErrors as $error) {
            $validationErrors['body'][] = [
                'name' => $error->getPropertyPath(),
                'message' => $error->getMessage(),
            ];
        }
        $queryErrors = $this->validator->validate($query, groups: $groupsQuery);
        foreach ($queryErrors as $error) {
            $validationErrors['query'][] = [
                'name' => $error->getPropertyPath(),
                'message' => $error->getMessage(),
            ];
        }
        if (count($validationErrors['body']) > 0 || count($validationErrors['query'])) {
            throw new ApiException(
                'Ошибки валидации',
                'Validation errors',
                Response::HTTP_BAD_REQUEST,
                validationArray: $validationErrors,
            );
        }
    }
}