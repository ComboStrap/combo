import ComboModal from "../ComboModal";


test('Modal Simple Button', () => {

    let modal = ComboModal.createTemporary();
    let htmlBody = "<p>Body</p>";
    modal.addBody(htmlBody)
    modal.build();
    let modalElement = modal.getElement();
    let id = modal.getId();
    let expected = `<div id="${id}" class="modal fade" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-md-down modal-lg" style="margin: 5rem auto; height: calc(100% - 9rem);">
    <div class="modal-content">
      <div class="modal-body">
        <p>
        Body
        </p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary">
        Close
        </button>
      </div>
    </div>
  </div>
</div>`;
    expect(modalElement.outerHTML).toEqualHtmlString(expected)

});
