$(function() {
    var winH = $(window).height(); //页面可视区域高度
    var i = 1;
    var canScroll=true;

    $(window).scroll(function() {
        var pageH = $(document.body).height();
        var scrollT = $(window).scrollTop(); //滚动条top
        var aa = (pageH - winH - scrollT) / winH;
        if (aa < 0.02 && canScroll==true) {
            $.ajax({
                url:LoadMoreUrl,
                type:'POST',
                async:false,
                dataType:'json',
                data:{
                    page:i
                },
                success:function(json){
                    if (json) {
                        var str = "";
                        var rankNum=i*10;
                        $.each(json, function(index, array) {
                            str +="<a href='index.php?c=Image&a=detail&id="+array['img_id']+"'>";
                            str +="<div class='cellBox'>";
                            str +="<div class='row'>";
                            str +="<div class='col-xs-5 pr_2 rel'>";
                            str +="<div class='pai'>"+(rankNum+index+1)+"</div>";
                            str +="<img src='"+imgUrl+array['img_file_name']+"' class='img-responsive' />";
                            str +="</div>";
                            str +="<div class='col-xs-7 pl_2'>";
                            str +="<div class='row mb4'><div class='col-xs-6 pr_2'><strong>编号：</strong>"+array['img_id']+"</div><div class='col-xs-6 pl_2 red'><strong>票数：</strong>"+array['ticket']+"</div></div>";
                            str +="<p><strong>作者：</strong>"+array['name']+"</p>";
                            str +="<p><strong>标题：</strong>"+array['title']+"</p>";
                            str +="<p class='brief_onerow' style='overflow:hidden;text-overflow:ellipsis;'><nobr><strong>简介：</strong>"+array['brief']+"</nobr></p>";
                            str +="</div>";
                            str +="</div>";
                            str +="</div>";
                            str +="</a>";
                        })
                        $("#listBox").append(str);
                        i++;	//页面计数器增加1
                    } else {
                        canScroll=false;
                        $(".nodata").show().html("别滚动了，已经到底了。。。");
                        return false;
                    }
                }
            })
        }
    });


    setTimeout(function(){
        $('#topNav').slideUp();
    },1000);


    $(window).scroll(function(){

        if($(window).scrollTop()<10){
            $('#topNav').slideDown();
        }else{
            $('#topNav').slideUp();
        }
    });


})