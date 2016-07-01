<?php
namespace Wafl\Extensions\Communication\Answers\Models\FunctionalModel;
class Answer extends \Wafl\Extensions\Communication\Answers\Models\DataModel\Answer
{
	function GetUsername()
	{
        $userClass = get_class(\Wafl\Core::$CURRENT_USER);
		$user = $userClass::GetInstance($this->_userId);
		return $user->Get_DisplayName();
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