Namespace("Wafl.Extensions.Communication.Blog.Models.DataModel");
Wafl.Extensions.Communication.Blog.Models.DataModel.BlogPost = DblEj.Data.PersistableModel.extend(
{
    init: function()
    {
        this._super();
        this.ServerObjectName = "\\Wafl\\Extensions\\Communication\\BlogAdmin\\Models\\FunctionalModel\\BlogPost";
        this.BlogPostId=null;
        this.PostDate=null;
        this.Title=null;
        this.UrlTitle=null;
        this.Contents=null;
        this.UserId=null;
        this.BlogCategoryId=null;
        this.IsPublished=null;
    },

    Get_ClientId: function()
    {
        return this.BlogPostId;
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
    },
    Get_IsPublished: function()
    {
        return this.IsPublished;
    },
    Set_IsPublished: function(newValue)
    {
        if (this.IsPublished !== newValue)
        {
            this.IsPublished = newValue;
            this.ModelChanged("IsPublished");
        }
        return this;
    },
    Get_PostDate: function()
    {
        return this.PostDate;
    },
    Set_PostDate: function(newValue)
    {
        if (this.PostDate !== newValue)
        {
            this.PostDate = newValue;
            this.ModelChanged("PostDate");
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
    },
    Get_UrlTitle: function()
    {
        return this.UrlTitle;
    },
    Set_UrlTitle: function(newValue)
    {
        if (this.UrlTitle !== newValue)
        {
            this.UrlTitle = newValue;
            this.ModelChanged("UrlTitle");
        }
        return this;
    },
    Get_Contents: function()
    {
        return this.Contents;
    },
    Set_Contents: function(newValue)
    {
        if (this.Contents !== newValue)
        {
            this.Contents = newValue;
            this.ModelChanged("Contents");
        }
        return this;
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
        return this;
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
    },Get_StorageGroup: function()
                            {return "Default";}
});