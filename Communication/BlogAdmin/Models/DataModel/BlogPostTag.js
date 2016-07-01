Namespace("Wafl.Extensions.Communication.BlogAdmin.Models.DataModel");
Wafl.Extensions.Communication.BlogAdmin.Models.DataModel.BlogPostTag = DblEj.Data.PersistableModel.extend(
{
    init: function()
    {
        this._super();
        this.ServerObjectName = "\\Wafl\\Extensions\\Communication\\BlogAdmin\\Models\\FunctionalModel\\BlogPostTag";
        this.BlogPostTagId=null;
        this.TagId=null;
        this.BlogPostId=null;
    },

    Get_ClientId: function()
    {
        return this.BlogPostTagId;
    },

    Get_BlogPostTagId: function()
    {
        return this.BlogPostTagId;
    },
    Set_BlogPostTagId: function(newValue)
    {
        if (this.BlogPostTagId !== newValue)
        {
            this.BlogPostTagId = newValue;
            this.ModelChanged("BlogPostTagId");
        }
        return this;
    },
    Get_TagId: function()
    {
        return this.TagId;
    },
    Set_TagId: function(newValue)
    {
        if (this.TagId !== newValue)
        {
            this.TagId = newValue;
            this.ModelChanged("TagId");
        }
        return this;
    },
    Get_BlogPostId: function()
    {
        return this.BlogPostId;
    },
    Set_BlogPostId: function(newValue)
    {
        if (this.BlogPostId !== newValue)
        {
            this.BlogPostId = newValue;
            this.ModelChanged("BlogPostId");
        }
        return this;
    },Get_StorageGroup: function()
                            {return "Default";}
});