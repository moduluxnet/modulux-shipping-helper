// AJAX Weight Suggestion
jQuery(document).ready(function($) {
    $('#modulux-suggest-weight').on('click', function() {
        var postId = $('#post_ID').val();
        if (!postId) return;

        $.ajax({
            url: modulux_weight_suggestor.ajax_url,
            type: 'POST',
            data: {
                action: 'modulux_suggest_weight',
                post_id: postId,
                nonce: modulux_weight_suggestor.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    var suggestion = response.data;
                    $('#_weight').val(suggestion.weight);
                    $('#_weight_unit').val(suggestion.unit);
                    alert(modulux_weight_suggestor.suggested_applied + ' ' + suggestion.weight + ' ' + suggestion.unit);
                } else {
                    alert(modulux_weight_suggestor.no_suggestion);
                }
            },
            error: function() {
                alert(modulux_weight_suggestor.error_fetching);
            }
        });
    });
});