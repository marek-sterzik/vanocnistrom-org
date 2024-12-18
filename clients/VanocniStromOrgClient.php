<?php

class VanocniStromOrgClient
{
    const DEFAULT_API_URL = "https://vanocnistrom.org/api";

    public function __construct(private string $code, private string $apiUrl = self::DEFAULT_API_URL)
    {
    }

    public function makeRequest(string $method, string $path, ?array $data = null): array
    {
        $url = rtrim($this->apiUrl, '/') . '/' . $this->code . '/' . ltrim($path, '/');

        $ch = curl_init($url);
        if($ch) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if ($data !== null) {
                $data = json_encode($data);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($data)));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            $result = curl_exec($ch);
            if (!is_string($result)) {
                throw new Exception("Request failed");
            }
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($status < 200 || $status >= 300) {
                throw new Exception("Invalid response");
            }
            $result = @json_decode($result, true);
            if (!is_array($result)) {
                throw new Exception("Invalid response: Not a JSON.");
            }
            return $result;
        }
        throw new Exception("Cannot initialize curl");
    }
}
