Namespace("Wafl.Extensions.Communication.Answers.Models.DataModel");
Wafl.Extensions.Communication.Answers.Models.DataModel.Category = DblEj.Data.Model.extend(
{
    init: function()
    {
        this._super();
        this.ServerObjectName = "\\Wafl\\Extensions\\Communication\\Answers\\Models\\FunctionalModel\\Category";
        this.CategoryId=null;
        this.Title=null;
    },

    Get_ClientId: function()
    {
        return this.CategoryId;
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
        return this;
    },
    Get_Title: function()
    {
        return this.Title;
    },
    Set_Title: function(newValue)
    {
        if (this.Title !== newValue)
        {
            this.Title = newValue;
            this.ModelChanged("Title");
        }
        return this;
    }
});