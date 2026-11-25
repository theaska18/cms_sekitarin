from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
SCRIPT = ROOT / "web" / "assets" / "js" / "autocomplete-clear.js"
FOOTER = ROOT / "web" / "application" / "views" / "admin" / "footer.php"


class AutocompleteClearContractTests(unittest.TestCase):
    def test_shared_library_is_loaded_globally(self):
        self.assertIn("assets/js/autocomplete-clear.js", FOOTER.read_text(encoding="utf-8"))

    def test_selected_values_become_readonly_and_show_a_clear_button(self):
        script = SCRIPT.read_text(encoding="utf-8")
        for marker in (
            "autocompleteselect", "autocompletechange",
            "prop('readonly', true)", "aria-readonly",
            "data-autocomplete-clear", "Clear selection",
            "paddingInlineEnd", "hiddenTarget(input[0]).val('')",
        ):
            self.assertIn(marker, script)

    def test_library_handles_known_lookup_target_attributes(self):
        script = SCRIPT.read_text(encoding="utf-8")
        for marker in (
            "data-hidden-target", "data-target", "data-account-target",
            "data-fiscal-year-target", "data-payment-lookup",
            "data-cash-lookup", "data-cash-account",
        ):
            self.assertIn(marker, script)

    def test_hierarchy_autocomplete_has_the_same_clear_and_readonly_behavior(self):
        script = (ROOT / "web" / "assets" / "js" / "admin-hierarchy-autocomplete.js").read_text(encoding="utf-8")
        for marker in (
            "function setSelected", "data-hierarchy-clear", "input.readOnly = selected",
            "target.value = ''", "setSelected(input, true)", "setSelected(input, false)",
        ):
            self.assertIn(marker, script)

    def test_clear_button_is_positioned_before_the_search_icon(self):
        script = SCRIPT.read_text(encoding="utf-8")
        self.assertIn("insetInlineEnd: '2.4rem'", script)
        self.assertIn("paddingInlineEnd', '4.5rem'", script)


if __name__ == "__main__":
    unittest.main()
