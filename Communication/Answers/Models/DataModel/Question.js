Namespace("Wafl.Extensions.Communication.Answers.Models.DataModel");
Wafl.Extensions.Communication.Answers.Models.DataModel.Question = DblEj.Data.Model.extend(
{
    init: function()
    {
        this._super();
        this.ServerObjectName = "\\Wafl\\Extensions\\Answers\\Models\\FunctionalModel\\Question";
        this.QuestionId=null;
        this.Question=null;
        this.Details=null;
        this.DateAsked=null;
        this.UserId=null;
        this.Tags=null;
        this.DateModerated=null;
        this.IsApproved=null;
        this.CategoryId=null;
    },

    Get_ClientId: function()
    {
        return this.QuestionId;
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
    Get_CategoryId: function()
    {
        return this.CategoryId;
    },
    Set_CategoryId: function(newValue)
    {
        if (this.CategoryId !== newValue)
        {
            this.CategoryId = newValue;
            this.ModelChanged("CategoryId");
        }
    },
    Get_Question: function()
    {
        return this.Question;
    },
    Set_Question: function(newValue)
    {
        if (this.Question !== newValue)
        {
            this.Question = newValue;
            this.ModelChanged("Question");
        }
    },
    Get_Details: function()
    {
        return this.Details;
    },
    Set_Details: function(newValue)
    {
        if (this.Details !== newValue)
        {
            this.Details = newValue;
            this.ModelChanged("Details");
        }
    },
    Get_DateAsked: function()
    {
        return this.DateAsked;
    },
    Set_DateAsked: function(newValue)
    {
        if (this.DateAsked !== newValue)
        {
            this.DateAsked = newValue;
            this.ModelChanged("DateAsked");
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
    Get_Tags: function()
    {
        return this.Tags;
    },
    Set_Tags: function(newValue)
    {
        if (this.Tags !== newValue)
        {
            this.Tags = newValue;
            this.ModelChanged("Tags");
        }
    },
    Get_DateModerated: function()
    {
        return this.DateModerated;
    },
    Set_DateModerated: function(newValue)
    {
        if (this.DateModerated !== newValue)
        {
            this.DateModerated = newValue;
            this.ModelChanged("DateModerated");
        }
    },
    Get_IsApproved: function()
    {
        return this.IsApproved;
    },
    Set_IsApproved: function(newValue)
    {
        if (this.IsApproved !== newValue)
        {
            this.IsApproved = newValue;
            this.ModelChanged("IsApproved");
        }
    }
})