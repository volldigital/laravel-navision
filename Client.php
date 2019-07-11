<?php

namespace VOLLdigital\LaravelNtlm;

use Illuminate\Support\Collection;

class Client {

    protected $host;

    protected $authType;

    protected $username;

    protected $password;

    public function __construct(array $config)
    {
        $this->host     = $config['ntlm_host'];
        $this->authType = $config['ntlm_auth_type'];
        $this->username = $config['ntlm_user'];
        $this->password = $config['ntlm_password'];
    }

    /**
     * Fetch collection from NAV
     *
     * @param  string $uri
     * @return Collection|null
     */
    public function fetchCollection(string $uri) : ?Collection
    {
        $curl = curl_init();

        $this->setCurlOptions($curl, $uri);

        $output = json_decode(
            curl_exec($curl)
        );

        curl_close($curl);

        if (isset($output->error)) {
            $this->parseError($output->error);
        }

        if (!isset($output->value)) {
            return null;
        }

        return collect($output->value)->map(function($object) {
            return (array)$object;
        });
    }

    /**
     * Fetch one record from NAV
     *
     * @param  string $uri
     * @return Collection|null
     */
    public function fetchOne(string $uri, string $key, $number) : array
    {
        $curl = curl_init();

        $this->setCurlOptions($curl, "$uri($key='$number')");

        $output = json_decode(
            curl_exec($curl)
        );

        curl_close($curl);

        if (isset($output->error)) {
            $this->parseError($output->error);
        }

        return (array)$output;
    }

    /**
     * Set NTLM curl options
     *
     * @param Curl $curl Curl handler
     * @param string $uri resource
     */
    protected function setCurlOptions($curl, $uri) : void
    {
        switch($this->authType) {
            case 'credentials':
            default:
                curl_setopt($curl, CURLOPT_URL, "$this->host/$uri");
                curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_NTLM|CURLAUTH_BASIC);
                curl_setopt($curl, CURLOPT_UNRESTRICTED_AUTH, true);
                curl_setopt($curl, CURLOPT_USERPWD, "$this->username:$this->password");
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            break;
        }
    }

    /**
     * Parse error response
     *
     * @param  stdClass $error
     * @return void
     * @throws RunTimeException
     */
    protected function parseError($error) : void
    {
        switch($error->code) {
            case 'BadRequest_ResourceNotFound':
                http_response_code(404);
                throw new \RunTimeException('Resource not found');
            break;
            case 'BadRequest_NotFound':
                http_response_code(404);
                throw new \RunTimeException('Route not found');
            break;
            default:
                throw new \RunTimeException($error->message);
            break;
        }
    }

}
