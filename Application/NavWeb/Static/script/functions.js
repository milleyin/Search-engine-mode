function clearString(s){ 
    var pattern = new RegExp("[`~!@#$^&*()=|{}':;',\\[\\].<>/?~！@#￥……&*（）&;|{}【】‘；：”“'。，、？]") 
    var rs = ""; 
    for (var i = 0; i < s.length; i++) { 
        rs = rs+s.substr(i, 1).replace(pattern, ''); 
    } 
    return rs;  
}
function search(){
    var param = $('#txtSearch').val();
    var page =1;
    var params = param.replace(/[ \/\\\@\!\#\$\%\^\&\*\(\)\-\+\=\|\—\_\-]/g,"");
    var params = encodeURI(clearString(params));
    
    if(params==''||params=='输入关键字'){
        alert("请输入关键字再搜索！");
        return false;
    }else{
        window.location.href = '/Search.Go-'+params+'-'+page;
        return true;
    }
}