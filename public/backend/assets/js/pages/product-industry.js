var selectedProductIds = [];
/*This code not used anymore pages*/
$(document).ready(function () {
    function initProductAutocomplete($input) {
        if (
            $input.hasClass("ui-autocomplete-input") &&
            $input.autocomplete("instance")
        ) {
            try {
                $input.autocomplete("destroy");
            } catch (e) {
                console.log("Error destroying autocomplete:", e);
            }
        }
        $input
            .autocomplete({
                source: function (request, response) {
                    var $loader = $input
                        .closest(".input-group")
                        .find(".product-loader");
                    var $refreshIcon = $input.closest(".input-group").find("i");

                    $loader.show();
                    $refreshIcon.hide();

                    $.ajax({
                        url: window.productAutocompleteUrl,
                        type: "GET",
                        data: {
                            query: request.term,
                            selected_ids: selectedProductIds,
                        },
                        success: function (data) {
                            $loader.hide();
                            $refreshIcon.show();

                            if (Array.isArray(data)) {
                                response(
                                    $.map(data, function (product) {
                                        return {
                                            label: product.title,
                                            value: product.title,
                                            id: product.id,
                                            title: product.title,
                                        };
                                    }),
                                );
                            } else {
                                response([]);
                            }
                        },
                        error: function (xhr, status, error) {
                            $loader.hide();
                            $refreshIcon.show();
                            console.log("Autocomplete Error:", error);
                            response([]);
                        },
                    });
                },
                minLength: 0,
                delay: 300,
                select: function (event, ui) {
                    var row = $(this).closest("tr");

                    row.find(".product_id").val(ui.item.id);
                    row.find(".product-name").val(ui.item.title);
                    $(this).val(ui.item.title);

                    if (!selectedProductIds.includes(ui.item.id.toString())) {
                        selectedProductIds.push(ui.item.id.toString());
                    }

                    console.log("Selected:", ui.item);
                    return false;
                },
                change: function (event, ui) {
                    var row = $(this).closest("tr");
                    if (!ui.item && $(this).val() === "") {
                        var productId = row.find(".product_id").val();
                        if (productId) {
                            selectedProductIds = selectedProductIds.filter(
                                function (id) {
                                    return id != productId;
                                },
                            );
                            row.find(".product_id").val("");
                            row.find(".product-name").val("");
                        }
                    }
                },
            })
            .autocomplete("instance")._renderItem = function (ul, item) {
            var term = this.term || "";
            var regex = new RegExp(
                "(" + term.replace(/[.*+?^${}()|[\]\\]/g, "\\$&") + ")",
                "gi",
            );
            var highlighted = item.label.replace(regex, "<strong>$1</strong>");
            return $("<li>")
                .append("<div>" + highlighted + "</div>")
                .appendTo(ul);
        };
    }
    $(".product-autocomplete").each(function () {
        initProductAutocomplete($(this));
    });
    $(document).on("click", "#addMore", function () {
        var newRow = `
            <tr>
                <td>
                    <div class="position-relative">
                        <div class="input-group">
                            <input type="text" name="product_name[]" class="form-control product-autocomplete" autocomplete="off">
                            <span class="input-group-text">
                                <i class="ti ti-refresh">⟳</i>
                                <div class="spinner-border spinner-border-sm product-loader" style="display:none;">⏳</div>
                            </span>
                        </div>
                        <input type="hidden" name="product_id[]" class="product_id">
                    </div>
                </td>
                <td>
                    <input type="text" name="product-name-display[]" class="form-control product-name" readonly>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-row">Remove</button>
                </td>
            </tr>
        `;
        $("#productTable tbody").append(newRow);
        var $newInput = $("#productTable tbody tr:last .product-autocomplete");
        initProductAutocomplete($newInput);
    });
    $(document).on("click", ".remove-row", function () {
        var row = $(this).closest("tr");
        var productId = row.find(".product_id").val();

        if (productId) {
            selectedProductIds = selectedProductIds.filter(function (id) {
                return id != productId;
            });
        }
        row.remove();
    });

    $(document).on("input", ".product-autocomplete", function () {
        var row = $(this).closest("tr");

        if ($(this).val() === "") {
            var productId = row.find(".product_id").val();

            if (productId) {
                row.find(".product_id").val("");
                row.find(".product-name").val("");

                selectedProductIds = selectedProductIds.filter(function (id) {
                    return id != productId;
                });
            }
        }
    });
});
