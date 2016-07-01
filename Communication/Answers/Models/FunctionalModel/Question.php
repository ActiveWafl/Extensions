<?php
namespace Wafl\Extensions\Communication\Answers\Models\FunctionalModel;
class Question extends \Wafl\Extensions\Communication\Answers\Models\DataModel\Question
{
	private $_acceptedAnswer;
	private function _loadAcceptedAnswer()
	{
		if (!$this->_acceptedAnswer)
		{
			$this->_acceptedAnswer = Answer::SelectFirst("QuestionId=$this->_questionId and AnswerAccepted=1");
		}
	}
	function GetUsername()
	{
        $userClass = get_class(\Wafl\Core::$CURRENT_USER);
		$user = $userClass::GetInstance($this->_userId);
		return $user->Get_DisplayName();
	}
	public function GetIsAnswered()
	{
		$this->_loadAcceptedAnswer();
		return is_object($this->_acceptedAnswer)?true:false;
	}
	public function GetAnswerCount()
	{
		return Answer::Count("QuestionId=$this->_questionId") ;
	}
	public function GetAnswers()
	{
		return Answer::Select("QuestionId=$this->_questionId","DateAnswered desc") ;
	}
	public function GetDateAnswered()
	{
		if ($this->GetIsAnswered())
		{
			return $this->_acceptedAnswer->Get_DateAnswered();
		} else {
			return 0;
		}
	}
	public function GetAnsweredByUserName()
	{
		if ($this->GetIsAnswered())
		{
            $userClass = get_class(\Wafl\Core::$CURRENT_USER);
			$user = $userClass::GetInstance($this->_acceptedAnswer->Get_UserId());
			return $user->Get_DisplayName();
		} else {
			return "";
		}
	}
    public function GetCategory()
    {
        if ($this->_categoryId)
        {
            $categoryClass = self::$extension->Get_CategoryClass();
            return new $categoryClass($this->_categoryId);
        } else {
            return null;
        }
    }
}
?>