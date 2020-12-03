
# Module configuration
module.tx_wpfalcleaner_tools_wpfalcleanerfalcleaner {
  persistence {
    storagePid = {$module.tx_wpfalcleaner_falcleaner.persistence.storagePid}
  }
  view {
    templateRootPaths.0 = EXT:wp_falcleaner/Resources/Private/Templates/
    templateRootPaths.1 = {$module.tx_wpfalcleaner_falcleaner.view.templateRootPath}
    partialRootPaths.0 = EXT:wp_falcleaner/Resources/Private/Partials/
    partialRootPaths.1 = {$module.tx_wpfalcleaner_falcleaner.view.partialRootPath}
    layoutRootPaths.0 = EXT:wp_falcleaner/Resources/Private/Layouts/
    layoutRootPaths.1 = {$module.tx_wpfalcleaner_falcleaner.view.layoutRootPath}
  }
}
