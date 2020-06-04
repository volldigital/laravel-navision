<?php

namespace VOLLdigital\LaravelNavision;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class Client {

    /**
     * NAV host
     *
     * @var string
     */
    protected $host;

    /**
     * NAV Auth type
     *
     * @var string
     */
    protected $authType;

    /**
     * NAV Auth username
     *
     * @var string
     */
    protected $username;

    /**
     * NAV auth password
     *
     * @var string
     */
    protected $password;

    /**
     * NAV auth token
     *
     * @var string
     */
    protected $token;

    public function __construct(array $config)
    {
        $this->host     = rtrim($config['ntlm_host'] ?? '', '/');
        $this->authType = $config['ntlm_auth_type'] ?? '';
        $this->username = $config['ntlm_user'] ?? '';
        $this->password = $config['ntlm_password'] ?? '';
        $this->token    = $config['ntlm_token'] ?? '';
    }

    /**
     * Fetch amount of items per collection from NAV
     *
     * @param  string $uri
     * @param  bool $chunk
     * @return Collection|null
     */
    public function countCollection(string $uri) : ?int
    {
        $curl = curl_init();

        $uri = ltrim($uri, '/') . '/$count';

        $this->setCurlOptions($curl, $uri);

        $output = curl_exec($curl);

        $err = curl_error($curl);

        if (strlen($err) !== 0) {
            throw new \RunTimeException($err);
        }

        curl_close($curl);

        $output = (int)filter_var($output, FILTER_SANITIZE_NUMBER_INT);

        return $output > 0 ? $output : null;
    }

    /**
     * Fetch collection from NAV
     *
     * @param  string $uri
     * @param  bool $chunk
     * @return Collection|null
     */
    public function fetchCollection(string $uri, bool $chunk = false) : ?Collection
    {
        $curl = curl_init();

        $this->setCurlOptions($curl, ltrim($uri, '/'));

        if ($chunk) {
            $tempFile = 'temp/curl_' . uniqid() . '.temp';

            Storage::disk('local')->put($tempFile, '');

            curl_setopt($curl, CURLOPT_WRITEFUNCTION, function(&$curl, $data) use($tempFile) {
                Storage::disk('local')->append($tempFile, $data);

                return strlen($data);
            });
        }

        $output = json_decode(
            curl_exec($curl)
        );

        $err = curl_error($curl);

        if (strlen($err) !== 0) {
            $err = $this->cleanUpCurlError($err);

            throw new \RunTimeException($err);
        }

        curl_close($curl);

        if ($chunk) {
            $output = json_decode(Storage::disk('local')->get($tempFile));
            Storage::disk('local')->delete($tempFile);
        }

        if (isset($output->error)) {
            $this->parseError($output->error);
        } elseif (!isset($output->value)) {
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
     * @param  string $key
     * @param  mixed $number
     * @return array
     */
    public function fetchOne(string $uri, string $key, $number) : array
    {
        $curl = curl_init();
        $uri = ltrim($uri, '/');

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
     * Write data to navision
     *
     * @param string $endpoint
     * @param array $body
     * @return array
     */
    public function writeData(string $endpoint, array $body) : array
    {
        $curl = curl_init();
        $jsonData = json_encode($body);

        $this->setCurlOptions($curl, ltrim($endpoint, '/'));

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);

        if (($response = curl_exec($curl)) === false) {
            $curlError = $this->cleanUpCurlError(curl_error($curl));

            throw new RuntimeException($curlError);
        }

        curl_close($curl);

        return (array)json_decode($response);
    }

    /**
     * Clean up curl error
     *
     * @param string $error
     * @return void
     */
    protected function cleanUpCurlError($error) {
        // hide url
        if (strpos($error, 'Could not resolve host') !== false) {
            return 'Could not resolve host';
        }

        return $error;
    }

    /**
     * Set NTLM curl options
     *
     * @param Curl $curl Curl handler
     * @param string $uri resource
     * @return void
     */
    protected function setCurlOptions($curl, string $uri) : void
    {
        if (empty($this->host) || empty($this->username) || empty($this->password)) {
            throw new \RunTimeException('No configuration given');
        }

        switch($this->authType) {
            case 'credentials':
            default:
                curl_setopt($curl, CURLOPT_URL, "$this->host/$uri");
                curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_NTLM | CURLAUTH_BASIC);
                curl_setopt($curl, CURLOPT_UNRESTRICTED_AUTH, true);
                curl_setopt($curl, CURLOPT_USERPWD, "$this->username:$this->password");
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            break;
            case 'token':
                // todo
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

    /**
     * Ping URL
     *
     * @return bool
     */
    public function ping() : bool
    {
        $host = str_replace(['http://', 'https://'], '', $this->host);

        try {
            $fs = fsockopen($host);
        } catch(\Exception $e) {
            return false;
        }

        if (!$fs) {
            return false;
        }

        fclose($fs);

        return true;
    }

}
