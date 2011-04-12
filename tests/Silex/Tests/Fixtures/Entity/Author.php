<?php
namespace Silex\Tests\Fixtures\Entity;
class Author
{
	private $firstName;
	private $lastName;
	public function setFirstName($firstName)
	{
		$this->firstName = $firstName;
	}
	public function getFirstName()
	{
		return $this->firstName;
	}
	public function getLastName()
	{
		return $this->lastName;
	}
	public function setLastName($lastName)
	{
		$this->lastName = $lastName;
	}
	public static function loadValidatorMetadata(\Symfony\Component\Validator\Mapping\ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('firstName', new \Symfony\Component\Validator\Constraints\NotBlank());
        $metadata->addPropertyConstraint('firstName', new \Symfony\Component\Validator\Constraints\MinLength(3));
        $metadata->addPropertyConstraint('lastName', new \Symfony\Component\Validator\Constraints\NotBlank(array(
				'message' => 'Last Name should not be blank.',
			)));
        $metadata->addPropertyConstraint('lastName', new \Symfony\Component\Validator\Constraints\MinLength(3));
    }
}