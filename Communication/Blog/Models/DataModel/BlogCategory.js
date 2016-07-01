Namespace("Wafl.Extensions.Communication.Blog.Models.DataModel");
Wafl.Extensions.Communication.Blog.Models.DataModel.BlogCategory = DblEj.Data.PersistableModel.extend(
{
    init: function()
    {
        this._super();
        this.ServerObjectName = "\\Wafl\\Extensions\\Communication\\Blog\\Models\\FunctionalModel\\BlogCategory";
        this.BlogCategoryId=null;
        this.Title=null;
    },

    Get_ClientId: function()
    {
        return this.BlogCategoryId;
    },

    Get_BlogCategoryId: function()
    {
        return this.BlogCategoryId;
    },
    Set_BlogCategoryId: function(newValue)
    {
        if (this.BlogCategoryId !== newValue)
        {
            this.BlogCategoryId = newValue;
            this.ModelChanged("BlogCategoryId");
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
    },Get_StorageGroup: function()
                            {return "Default";}
});