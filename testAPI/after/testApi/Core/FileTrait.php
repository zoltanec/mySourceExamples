<?php


namespace Test\testApi\Core;

use Common\Model\Storage\File;

trait FileTrait
{

    /**
     * @param string|null $entityId
     * @param string|null $type
     * @return File
     * @throws \yii\base\Exception
     */
    public function fakeFile(string $entityId = null, string $type = null): File
    {
        if (!$entityId) {
            $entityId = $this->fakeUser()->getId();
            $type     = File::TYPE_PASSPORT;
        }

        $faker = $this->getFaker();

        $file = (new File())->setAttributes([
            'id'        => $faker->regexify('[a-f0-9]{24}'),
            'entity_id' => $entityId,
            'type'      => $type,
        ]);

        if (!$file->save()) {
            throw new \RuntimeException('Failed to create file '
                . $file->getJsonErrors());
        }

        return $file;
    }
}