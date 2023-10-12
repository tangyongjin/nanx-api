clear_log = function() {

    //windows.location.href 当前页面打开URL页面
    var url = window.location.href
    //  alert(url);
    console.log(url);
    //切割成数组 http: //    119.254.119.59:8502 / log
    var arr = url.split("/");
    //alert(arr);

    console.log(arr)//
    // var domain=  arr[0] + "//" + arr[2];
    var domain = arr[0] + "//" + arr[2] + "/" + arr[3];
    console.log(domain)

    // alert(domain) 

    // urlx=domain + '/log/clearlog';
    urlx = domain + '/log/clearlog';

    alert(urlx);

    $.ajax({ url: urlx, async: false });
    location.reload();

}


hide_class = function(clsname) {
    var dx = document.getElementsByClassName(clsname);
    alert(1);
    for (var i = 0; i < dx.length; i++) { dx[i].style.display = "none" };

}



function gotop() {
    document.body.scrollTop = 0;
    document.documentElement.scrollTop = 0;
}


function gobottom() {
    window.scrollTo(0, document.body.scrollHeight);

}




