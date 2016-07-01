Namespace("Wafl.Extensions.Communication.AnswersAdmin.Models.DataModel");
Wafl.Extensions.Communication.AnswersAdmin.Models.DataModel.AnswerComment = DblEj.Data.Model.extend(
{
    init: function()
    {
        this._super();
        this.ServerObjectName = "\\Wafl\\Extensions\\Communication\\AnswersAdmin\\Models\\FunctionalModel\\AnswerComment";
        this.AnswerCommentId=null;
        this.UserId=null;
        this.AnswerId=null;
        this.CommentDate=null;
        this.Comment=null;
    },

    Get_ClientId: function()
    {
        return this.AnswerCommentId;
    },

    Get_AnswerCommentId: function()
    {
        return this.AnswerCommentId;
    },
    Set_AnswerCommentId: function(newValue)
    {
        if (this.AnswerCommentId !== newValue)
        {
            this.AnswerCommentId = newValue;
            this.ModelChanged("AnswerCommentId");
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
    Get_CommentDate: function()
    {
        return this.CommentDate;
    },
    Set_CommentDate: function(newValue)
    {
        if (this.CommentDate !== newValue)
        {
            this.CommentDate = newValue;
            this.ModelChanged("CommentDate");
        }
    },
    Get_Comment: function()
    {
        return this.Comment;
    },
    Set_Comment: function(newValue)
    {
        if (this.Comment !== newValue)
        {
            this.Comment = newValue;
            this.ModelChanged("Comment");
        }
    }
})