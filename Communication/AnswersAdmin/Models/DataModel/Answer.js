Namespace("Wafl.Extensions.Communication.AnswersAdmin.Models.DataModel");
Wafl.Extensions.Communication.AnswersAdmin.Models.DataModel.Answer = DblEj.Data.Model.extend(
{
    init: function()
    {
        this._super();
        this.ServerObjectName = "\\Wafl\\Extensions\\Communication\\AnswersAdmin\\Models\\FunctionalModel\\Answer";
        this.AnswerId=null;
        this.QuestionId=null;
        this.UserId=null;
        this.Answer=null;
        this.DateAnswered=null;
        this.AnswerAccepted=null;
        this.UpVotes=null;
        this.DownVotes=null;
    },

    Get_ClientId: function()
    {
        return this.AnswerId;
    },

    Get_AnswerId: function()
    {
        return this.AnswerId;
    },
    Set_AnswerId: function(newValue)
    {
        if (this.AnswerId !== newValue)
        {
            this.AnswerId = newValue;
            this.ModelChanged("AnswerId");
        }
    },
    Get_QuestionId: function()
    {
        return this.QuestionId;
    },
    Set_QuestionId: function(newValue)
    {
        if (this.QuestionId !== newValue)
        {
            this.QuestionId = newValue;
            this.ModelChanged("QuestionId");
        }
    },
    Get_UserId: function()
    {
        return this.UserId;
    },
    Set_UserId: function(newValue)
    {
        if (this.UserId !== newValue)
        {
            this.UserId = newValue;
            this.ModelChanged("UserId");
        }
    },
    Get_Answer: function()
    {
        return this.Answer;
    },
    Set_Answer: function(newValue)
    {
        if (this.Answer !== newValue)
        {
            this.Answer = newValue;
            this.ModelChanged("Answer");
        }
    },
    Get_DateAnswered: function()
    {
        return this.DateAnswered;
    },
    Set_DateAnswered: function(newValue)
    {
        if (this.DateAnswered !== newValue)
        {
            this.DateAnswered = newValue;
            this.ModelChanged("DateAnswered");
        }
    },
    Get_AnswerAccepted: function()
    {
        return this.AnswerAccepted;
    },
    Set_AnswerAccepted: function(newValue)
    {
        if (this.AnswerAccepted !== newValue)
        {
            this.AnswerAccepted = newValue;
            this.ModelChanged("AnswerAccepted");
        }
    },
    Get_UpVotes: function()
    {
        return this.UpVotes;
    },
    Set_UpVotes: function(newValue)
    {
        if (this.UpVotes !== newValue)
        {
            this.UpVotes = newValue;
            this.ModelChanged("UpVotes");
        }
    },
    Get_DownVotes: function()
    {
        return this.DownVotes;
    },
    Set_DownVotes: function(newValue)
    {
        if (this.DownVotes !== newValue)
        {
            this.DownVotes = newValue;
            this.ModelChanged("DownVotes");
        }
    }
})