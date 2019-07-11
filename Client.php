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

        if (!isset($output->value)) {
            return null;
        }

        return collect($output->value)->map(function($object) {
            return (array)$object;
        });
    }

    /**
     * Set NTLM curl options
     *
     * @param Curl $curl Curl handler
     * @param string $uri resource
     */
    protected function setCurlOptions($curl, $uri)
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

}
