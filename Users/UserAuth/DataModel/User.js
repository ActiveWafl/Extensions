Namespace("Wafl.Extensions.Users.UserAuth.DataModel");
Wafl.Extensions.Users.UserAuth.DataModel.User = DblEj.Data.Model.extend(
{
    init: function()
    {
        this._super();
        this.ServerObjectName = "\\Wafl\\Extensions\\Users\\UserAuth\\FunctionalModel\\User";
        this.UserId=null;
        this.UserGroupId=null;
        this.EmailAddress=null;
        this.LastLogin=null;
    },
    Get_ClientId: function()
    {
        return this.UserId;
    },
    Get_UserId: function()
    {
        return this.UserId;
    },
    Set_UserId: function(newValue)
    {
        if (this.UserId != newValue)
        {
            this.UserId = newValue;
            this.ModelChanged("UserId");
        }
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
    Get_EmailAddress: function()
    {
        return this.EmailAddress;
    },
    Set_EmailAddress: function(newValue)
    {
        if (this.EmailAddress != newValue)
        {
            this.EmailAddress = newValue;
            this.ModelChanged("EmailAddress");
        }
    },
    Get_LastLogin: function()
    {
        return this.LastLogin;
    },
    Set_LastLogin: function(newValue)
    {
        if (this.LastLogin != newValue)
        {
            this.LastLogin = newValue;
            this.ModelChanged("LastLogin");
        }
    }
})