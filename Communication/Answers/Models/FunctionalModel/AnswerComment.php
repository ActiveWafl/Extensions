<?php
namespace Wafl\Extensions\Communication\Answers\Models\FunctionalModel;
class AnswerComment extends \Wafl\Extensions\Communication\Answers\Models\DataModel\AnswerComment
{
	function GetUsername()
	{
		$user = new User($this->_userId);
		return $user->GetDisplayName();
	}
}
?>