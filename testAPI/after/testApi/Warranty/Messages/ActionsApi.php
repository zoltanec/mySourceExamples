<?php
declare(strict_types=1);

namespace Test\testApi\Warranty\Messages {


    use Common\Warranty\Messages\Facade;
    use Test\testApi\Core\TestApi;
    use Test\testApi\Warranty\WarrantyApi;

    class ActionsApi extends TestApi
    {
        private $warrantyApi;
        private $fMessage;
        private $facade;

        protected function getFaker()
        {
            return $this->fMessage;
        }

        public function __construct(Facade $facade, WarrantyApi $wApi, FakeMessage $fmessage)
        {
            $this->facade      = $facade;
            $this->warrantyApi = $wApi;
            $this->fMessage = $fmessage;
        }

    }
}