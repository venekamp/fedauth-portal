// Main function
$(document).on('pagecreate', '#main', function() {
    // Use exports from locally defined module
    var keysController = new gauth.KeysController();
    keysController.init();

    $("#loadasp").click(function(){
        $("#aspresult").load("asp.php");
    });

    $('#aspresult').on('click', function (){
        var text = $(this).text();
        var $this = $(this);
        var $input = $('<input type=text>');
        $input.prop('value', text);
        $input.appendTo($this.parent());
        $input.focus();
        $input.select();
        $this.hide();
        $input.focusout(function(){
            $this.show();
            $input.remove();
        });
    });
});
