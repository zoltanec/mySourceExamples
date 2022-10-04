<?php

namespace Test\testApi\Common;

use Common\Model\Company\Company;
use Common\Model\User\User;
use Common\Staff\Model\CompanyStaff;
use Test\testApi\Company\FakeCompany;
use Test\testApi\Company\FakeComplexCompany;
use Test\testApi\Company\Staff\FakeCompanyStaff;
use Test\testApi\Core\BaseFaker;
use Test\testApi\Core\FakeDataTrait;
use Test\testApi\User\FakeUser;

abstract class ComplexFakerDecorator extends BaseFaker
{
    use FakeDataTrait;

    private $wrappedUser;
    private $wrappedCompany;
    private $wrappedStaff;

    private $user;
    private $company;

    /**
     * ComplexFakerDecorator constructor.
     *
     * @param FakeUser $user
     * @param FakeComplexCompany $company
     * @param FakeCompanyStaff $staff
     */
    public function __construct(FakeUser $user, FakeComplexCompany $company, FakeCompanyStaff $staff)
    {
        $this->wrappedUser    = $user;
        $this->wrappedCompany = $company;
        $this->wrappedStaff   = $staff;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getCompany()
    {
        return $this->company;
    }

    public function getWrappedUser(): FakeUser
    {
        return $this->wrappedUser;
    }

    public function getWrappedCompany(): FakeCompany
    {
        return $this->wrappedCompany;
    }

    public function getWrappedStaff()
    {
        return $this->wrappedStaff;
    }

    public function fakeCompany(): Company
    {
        return $this->getWrappedCompany()->fakeCompany();
    }

    public function fakeUser(): User
    {
        return $this->getWrappedUser()->fakeUser();
    }

    public function fakeStaff(Company $company = null, User $user = null): CompanyStaff
    {
        $this->user = $user ?? $this->fakeUser();
        $this->company = $company ?? $this->fakeCompany();

        return $this->getWrappedStaff()->fakeStaff($this->getCompany(), $this->getUser());
    }

    public function fakeCompanyBank()
    {
        return $this->getWrappedCompany()->fakeCompanyBank();
    }
}