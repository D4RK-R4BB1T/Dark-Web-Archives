<?php






interface FileLoader
{
    /**
     * @param $file
     * @return PublicKeyInterface
     */
    public function loadPublicKeyData($file);

    /**
     * @param $file
     * @return PrivateKeyInterface
     */
    public function loadPrivateKeyData($file);
}
