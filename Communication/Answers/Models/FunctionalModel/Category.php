<?php
namespace Wafl\Extensions\Communication\Answers\Models\FunctionalModel;
class Category extends \Wafl\Extensions\Communication\Answers\Models\DataModel\Category
{
	function GetUsername()
	{
		$user = new User($this->_userId);
		return $user->GetDisplayName();
	}
	public function AddComment($userId,$commentText)
	{
		$newComment = new AnswerComment();
		$newComment->Set_UserId($userId);
		$newComment->Set_Comment($commentText);
		$newComment->Set_CommentDate(time());
		$newComment->Set_AnswerId($this->_answerId);
		$newComment->Save();
		return $newComment;
	}
	public function GetComments()
	{
		return AnswerComment::Select("AnswerId=$this->_answerId","CommentDate desc");
	}	
}
?>