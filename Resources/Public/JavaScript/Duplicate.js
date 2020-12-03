define([
  'jquery',
], function ($) {
  $(document).ready(function(){
    // count discspace
    total = 0;
    $(".duplicateTable TBODY TR").each(function(index, element) {
      number = $(element).find('TD').eq(1).text() -1;
      filesize = $(element).find('TD').eq(2).data('value');
      discspace = number * filesize;
      total = total + discspace;
    });
    $("#purge").text(total.toLocaleString());

    // add rule:
    $("#newRule").on("click", function() {
      template = $("#rule").html();
      $('#rulesPlace').append(template);
      total = $('#rulesPlace .rule').length;
      setFoldername(total-1);
      return false;
    });

    // delete rule:
    $(document).on( "click", ".deleteRule", function() {
      confirm('Delete rule?');
      $(this).closest('.rule').remove();
      return false;
    });

    // move up rule:
    $(document).on( "click", ".moveupRule", function() {
      index = $(this).closest('.rule').index();
      indexUp = index-1;
      if (index != 0) {
        $('#rulesPlace .rule').eq(index).insertBefore($('#rulesPlace .rule').eq(indexUp));
      }
      return false;
    });

    // move down rule:
    $(document).on( "click", ".movedownRule", function() {
      index = $(this).closest('.rule').index();
      indexDown = index+1;
      total = $('#rulesPlace .rule').length;
      if (index != total) {
        $('#rulesPlace .rule').eq(index).insertAfter($('#rulesPlace .rule').eq(indexDown));
      }
      return false;
    });

    // change rule:
    $(document).on( "change", ".type", function() {
      index = $(this).closest('.rule').index();
      setFoldername(index);
    });

    function setFoldername(index) {
      show = $("#rulesPlace .rule").eq(index).find(".type").find(':selected').data('show');
      console.log(index);
      console.log(show);
      if (show === true) {
        $("#rulesPlace .rule").eq(index).find(".foldername").show();
      } else {
        $("#rulesPlace .rule").eq(index).find(".foldername").hide();
      }
    }
  });
});
