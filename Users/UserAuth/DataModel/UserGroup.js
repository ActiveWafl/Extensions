Namespace("Wafl.Extensions.Users.UserAuth.DataModel");
Wafl.Extensions.Users.UserAuth.DataModel.UserGroup = DblEj.Data.Model.extend(
{
    init: function()
    {
        this._super();
        this.ServerObjectName = "\\Wafl\\Extensions\\Users\\UserAuth\\FunctionalModel\\UserGroup";
        this.UserGroupId=null;
        this.Title=null;
    },

    Get_ClientId: function()
    {
        return this.UserGroupId;
    },

    Get_UserGroupId: function()
    {
        return this.UserGroupId;
    },
    Set_UserGroupId: function(newValue)
    {
        if (this.UserGroupId != newValue)
        {
            this.UserGroupId = newValue;
            this.ModelChanged("UserGroupId");
        }
    },
    Get_Title: function()
    {
        return this.Title;
    },
    Set_Title: function(newValue)
    {
        if (this.Title != newValue)
        {
            this.Title = newValue;
            this.ModelChanged("Title");
        }
    }
})