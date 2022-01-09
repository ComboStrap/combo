import ComboModal from "../ComboModal";


test('Modal Simple Button', () => {

    let modal = ComboModal.createTemporary();
    let htmlBody = "<p>Body</p>";
    modal.addBody(htmlBody)
    modal.build();
    let modalElement = modal.getElement();
    let id = modal.getModalId();
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
    modal.getElement().remove(); // the test afterward are checking the number of modal ...

});

test('Modal creation/destruction test', () => {

    /**
     * At creation time, the dom is not modified
     * @type {string}
     */
    let modalId = "modal-id";
    let modal = ComboModal.getOrCreate(modalId);
    let modalElement = document.getElementById(modalId);
    expect(modalElement).toBeNull();
    expect(modal.getElement()).not.toBeNull();
    modal.getElement().remove();

    /**
     * Rebuild it
     */
    let secondModalInstantiation = ComboModal.getOrCreate(modalId);
    secondModalInstantiation.show(); // add the modal in the DOM
    modalElement = document.getElementById(modalId);
    expect(modalElement).not.toBeNull();
    /**
     * GettingOrCreate / Showing twice
     * should not create two elements in the DOM
     * @type {ComboModal}
     */
    let thirdModalInstantiation = ComboModal.getOrCreate(modalId);
    expect(thirdModalInstantiation).toBe(secondModalInstantiation);
    thirdModalInstantiation.show();
    let modalElements = document.querySelectorAll(".modal");
    expect(modalElements).toHaveLength(1);
    let firstModalElement = modalElements[0];
    expect(firstModalElement).toBe(modalElement);
    /**
     * Remove
     */
    thirdModalInstantiation.getElement().remove();
    modalElements = document.querySelectorAll(".modal");
    expect(modalElements).toHaveLength(0); // no more in the DOM

});

test('Modal creation/destruction/reset test', () => {

    /**
     * Should not give any error
     */
    let modalId = "modal-id-reset-show";
    let modal = ComboModal.getOrCreate(modalId);
    modal.show();
    modal.reset();
    modal.show();
    modal.getElement().remove(); // the test afterward are checking the number of modal ...


})
