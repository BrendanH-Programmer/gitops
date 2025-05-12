provider "azurerm" {
  features {}
}

resource "azurerm_resource_group" "example" {
  name     = "example-resources"
  location = "UK SOUTH"
}

output "resource_group_name" {
  value = azurerm_resource_group.example.name
}