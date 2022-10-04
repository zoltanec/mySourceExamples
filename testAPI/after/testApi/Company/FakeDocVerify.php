<?php


namespace Test\testApi\Company;

use Common\Model\User\User;
use Common\Util\Helper\CommonHelper;
use Common\Warranty\Model\DocumentVerify;

class FakeDocVerify
{
    //Может быть декоратором банковского юзера
    /**
     * @param $fileId
     * @param User $user
     * @return DocumentVerify
     * @throws \Exception
     */
    protected function fakeDocVerify($fileId, User $user): DocumentVerify
    {
        $company = $this->fakeCompany();
        $this->fakeBankStaff($company, $user);

        $attributes = [
            'fileId'     => $fileId,
            'bankUserId' => $user->getId(),
            'bankId'     => $company->getId(),
            'status'     => DocumentVerify::getStatuses()[random_int(0, 1)],
        ];
        $attributes = CommonHelper::toUnderscore($attributes);

        $verifyDoc = (new DocumentVerify())->setAttributes($attributes);

        if (!$verifyDoc->save()) {
            throw new \RuntimeException('Проверка документа банком не создана '
                . $verifyDoc->getJsonErrors());
        }
        return $verifyDoc;
    }
}