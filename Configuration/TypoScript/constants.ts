
module.tx_wpfalcleaner_falcleaner {
  view {
    # cat=module.tx_wpfalcleaner_falcleaner/file; type=string; label=Path to template root (BE)
    templateRootPath = EXT:wp_falcleaner/Resources/Private/Templates/
    # cat=module.tx_wpfalcleaner_falcleaner/file; type=string; label=Path to template partials (BE)
    partialRootPath = EXT:wp_falcleaner/Resources/Private/Partials/
    # cat=module.tx_wpfalcleaner_falcleaner/file; type=string; label=Path to template layouts (BE)
    layoutRootPath = EXT:wp_falcleaner/Resources/Private/Layouts/
  }
  persistence {
    # cat=module.tx_wpfalcleaner_falcleaner//a; type=string; label=Default storage PID
    storagePid =
  }
}
