<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $expectedUser = env('admin.username', 'admin');
        $expectedPass = env('admin.password', 'admin123');

        $user = $request->getServer('PHP_AUTH_USER');
        $pass = $request->getServer('PHP_AUTH_PW');

        if ($user !== $expectedUser || $pass !== $expectedPass) {
            return service('response')
                ->setStatusCode(401)
                ->setHeader('WWW-Authenticate', 'Basic realm="UIU Recruitment Portal Admin"')
                ->setBody('Authentication required.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
