<?php
namespace Silex\Tests\Fixtures\Entity;
class Reader
{
    /**
     * @assert:NotBlank()
     * @assert:MinLength(limit = 3)
     */
	private $firstName;
    /**
     * @assert:NotBlank(message = "Last Name should not be blank.")
     * @assert:MinLength(limit = 3)
     */
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
}