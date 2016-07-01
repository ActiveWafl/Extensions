<?php
namespace Wafl\Extensions\Communication\AnswersAdmin\Models\FunctionalModel;
class AnswerComment extends \Wafl\Extensions\Communication\AnswersAdmin\Models\DataModel\AnswerComment
{
	function GetUsername()
	{
		$user = new User($this->_userId);
		return $user->GetDisplayName();
	}
}
?>