define(["core/templates", "core/modal_factory"], function (
  Templates,
  ModalFactory
) {
  const showModalWithPlaceholder = function () {
    return ModalFactory.create({
      body: Templates.render(
        "paygw_ifthenpay/ifthenpay_button_placeholder",
        {}
      ),
    }).then(function (modal) {
      modal.show();
    });
  };

  const process = function (component, paymentArea, itemId, description) {
    return showModalWithPlaceholder().then(function () {
      window.location.href =
        M.cfg.wwwroot +
        "/payment/gateway/ifthenpay/pay.php?" +
        "component=" +
        component +
        "&paymentarea=" +
        paymentArea +
        "&itemid=" +
        itemId +
        "&description=" +
        description;
      // Keep promise pending to avoid UI race conditions.
      return new Promise(function () {});
    });
  };

  return { process: process };
});
