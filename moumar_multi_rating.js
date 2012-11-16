jQuery(function($) {
  var rating_names = [
    "Nul",
    "A la limite",
    "Assez bien",
    "Très bien",
    "Excellent!"
  ];

  function restore_image_src() {
    var img = $(this);
    var text = img.parent(".mmr-rating").find(".text");
    img.attr("src", img.data("original_src"));
    if (!text.data("sticky")) {
      text.text("");
    }
  }

  $(".mmr-rating.editable > img")
    .hover(function() {
      var img = $(this);
      var mmr_rating = img.parent(".mmr-rating");
      var imgs = img.siblings("img").andSelf();
      var i = imgs.index(img);
      imgs
        .each(function(index) {
          var img = $(this);
          var bn = img.attr("src").replace(/_\w+\.gif/, '');
          var suffix = "off";
          if (i >= index) {
            suffix = "over";
          }
          img.attr("src", bn + "_" + suffix + ".gif");
        });

      mmr_rating
        .find(".text")
        .text(rating_names[i])
        .data("sticky", false);
    }, restore_image_src)
    .click(function() {
      var img = $(this);
      var mmr_rating = img.parent(".mmr-rating");
      var text = mmr_rating.find(".text");
      var imgs = img.siblings("img").andSelf();
      var i = imgs.index(img);

      text.text("envoi...")
          .data("sticky", true);
      imgs.each(function() {
        var img = $(this);
        img.data("original_src", img.attr("src"));
      });
      $.post(mmr_rating.data("post_url"), {
        post_id: mmr_rating.data("post_id"),
        rating_category: mmr_rating.data("rating_category"),
        rating: i+1
      }).complete(function(e) {
        text.text(e.responseText);
      });
    });

  $(".mmr-rating.editable").mouseout(function() {
    $(this).find("> img").each(restore_image_src);
  });
});
